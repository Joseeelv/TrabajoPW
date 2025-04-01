<?php
session_start();

include './.configDB.php';

require_once('./.configDB.php');
if (isset($_SESSION['conexión'])) {
    $connection = $_SESSION['conexión'];
} else {
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
}
?>
<html>

<head>
    <title>Carrito</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>

<body>
    <?php include('./navbar.php'); ?>
    <main>
        <h1>Carrito de Compras</h1>
        <h2>Productos en el carrito:</h2>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul>
                        <?php
                        try {
                            // Si no se han cargado las ofertas en la sesión, las traemos de la base de datos
                            if (!isset($_SESSION['ofertas'])) {
                                $query = "SELECT OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
                                $stmt = $connection->prepare($query);
                                $stmt->execute();
                                $_SESSION['ofertas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            }
                            $v_total = 0;  // Inicializamos el total de la compra
                            // Si hay productos en el carrito
                            if (isset($_SESSION['compra'])) {

                                echo "<ul>";  // Comenzamos a mostrar la lista de productos
                        
                                // Recorremos los productos del carrito
                                foreach ($_SESSION['compra'] as $p) {
                                    $descuento = $p['precio'] * $p['cantidad'];  // Calculamos el precio base del producto
                        
                                    // Verificamos si hay alguna oferta para este producto
                                    if (isset($_SESSION['ofertas'])) {
                                        foreach ($_SESSION['ofertas'] as $f) {
                                            if ($f['nombre'] == $p['nombre']) {
                                                $descuento *= (1 - $f['discount'] / 100);  // Aplicamos el descuento si existe una oferta
                                            }
                                        }
                                    }

                                    // Mostramos el nombre del producto y su precio con descuento
                                    echo "<li>" . $p['nombre'] . " " . $descuento . " ";
                                    foreach ($p['lista_ingredientes'] as $ingrediente) {
                                        echo "{$ingrediente[0]} -> {$ingrediente[1]}";
                                    }
                                    echo "</li>";
                                    $v_total += $descuento;  // Añadimos el precio al total de la compra
                                }
                                echo "</ul>";  // Terminamos la lista de productos
                            } else {
                                // Si no hay productos en el carrito, mostramos una lista vacía
                                echo "<ul></ul>";
                            }

                            // Creamos el formulario para confirmar la compra
                            echo "<form action=\"Carrito.php\" method=\"POST\">";
                            echo "Price: " . $v_total . "\t<input type=\"submit\" name=\"Confirmar\" value=\"Confirmar\"/>";
                            echo "</form>";

                            // Si el formulario es enviado y el usuario confirma la compra
                            if (isset($_POST["Confirmar"]) && $_POST["Confirmar"] == "Confirmar" && isset($_SESSION['compra'])) {
                                // Actualizamos los puntos del cliente basado en el total de la compra
                                $stmt = $connection->prepare("UPDATE CUSTOMERS SET points = points + ? WHERE user_id = ?");
                                $points = (intdiv($v_total, 10)) * 100;  // Calculamos los puntos a añadir
                                $stmt->bind_param("di", $points, $_SESSION['user_id']);
                                $stmt->execute();

                                // Insertamos la nueva orden en la base de datos
                                $stmt = $connection->prepare("INSERT INTO ORDERS(user_id, order_date, order_status) VALUES (?, ?, ?)");
                                $stmt->bind_param("iss", $_SESSION['user_id'], $order_date, $order_status);
                                $order_date = date('Y-m-d');  // Fecha de la orden
                                $order_status = 'Pending';  // Estado de la orden
                                $stmt->execute();

                                // Insertamos los productos de la compra en la tabla ORDER_ITEMS
                                foreach ($_SESSION['compra'] as $p) {
                                    $descuento = $p['precio'];  // Obtenemos el precio base del producto
                        
                                    // Aplicamos el descuento si hay alguna oferta
                                    if (isset($_SESSION['ofertas'])) {
                                        foreach ($_SESSION['ofertas'] as $f) {
                                            if ($f['nombre'] == $p['nombre']) {
                                                $descuento *= (1 - $f['discount'] / 100);  // Aplicamos el descuento
                                            }
                                        }
                                    }

                                    // Insertamos los productos de la orden en la tabla ORDER_ITEMS
                                    $order_id = $connection->insert_id;
                                    $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS(order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                                    $stmt->bind_param("iiid", $order_id, $p['id'], $p['cantidad'], $descuento);
                                    $stmt->execute();

                                }

                                // Limpiamos el carrito de compras en la sesión después de la compra
                                $_SESSION['compra'] = null;
                            }

                        } catch (Exception $e) {
                            // Si ocurre una excepción, redirigimos a la página de error 500
                            header("Location: 500.php");
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    <?php include('./footer.php'); ?>
</body>