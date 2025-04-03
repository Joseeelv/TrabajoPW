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
    <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>

<body>
    <?php include('./navbar.php'); ?>
    <main>
        <h1>Carrito de compra</h1>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul>
                        <?php
                        try {
                            // Si no se han cargado las ofertas en la sesión, las traemos de la base de datos
                            if (!isset($_SESSION['ofertasActivas'])) {
                                $query = "SELECT OFFERS.offer_text as of_name, OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img, OFFERS.cost as coronas, CUSTOMERS_OFFERS.used as used 
                                          FROM CUSTOMERS_OFFERS 
                                          JOIN OFFERS ON CUSTOMERS_OFFERS.offer_id = OFFERS.offer_id 
                                          JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id 
                                          WHERE CUSTOMERS_OFFERS.user_id = ?";
                                $stmt = $connection->prepare($query);
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $_SESSION['ofertasActivas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            }
                            $v_total = 0;  // Inicializamos el total de la compra

                        ?>
                            <h2>Ofertas activas:</h2>
                            <?php
                            // Mostramos las ofertas activas
                            foreach ($_SESSION['ofertasActivas'] as $f) {
                                echo "<ul>";
                                echo "<li>" . $f['of_name'] . "</li>";
                                echo "</ul>";
                            }
                            ?>
                            <h2>Productos en el carrito:</h2>
                        <?php
                            // Si hay productos en el carrito
                            if (isset($_SESSION['compra'])) {
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
                                        if (isset($_SESSION['ofertasActivas'])) {
                                            foreach ($_SESSION['ofertasActivas'] as $f) {
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

                                        $order_item_id = $connection->insert_id;

                                        if ($p['category'] == 'DRINK' || $p['category'] == 'DESSERT') {
                                            $stmt = $connection->prepare("SELECT stock from products where product_id = ?");
                                            $stmt->bind_param("i", $p['id']);
                                            $stmt->execute();

                                            $i = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                            $stmt = $connection->prepare("UPDATE Products SET stock = ? where ingredient_id = ?");
                                            $stmt->bind_param("i", $i[0]['stock'] - 1, $p['id']);
                                            $stmt->execute();
                                        } else {
                                            foreach ($p['lista_ingredientes'] as $i) {
                                                // Insertamos los ingredientes de la orden en la tabla ORDER_ITEMS_INGREDIENTS
                                                $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS_INGREDIENTS(order_item_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                                                $stmt->bind_param("iii", $order_item_id, $i[0], $i[1]);
                                                $stmt->execute();

                                                // Obtenemos el stock actual del ingrediente
                                                $stmt = $connection->prepare("SELECT ingredients.stock from ingredients where ingredients.ingredient_id = ?");
                                                $stmt->bind_param("i", $i[0]);
                                                $stmt->execute();
                                                $s = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                                                // Actualizamos el stock de los ingredientes
                                                $stmt = $connection->prepare("UPDATE Ingredients SET stock = ? where ingredient_id = ?");
                                                $aux = $s[0]['stock'] - $i[1];
                                                $stmt->bind_param("ii", $aux, $i[0]);
                                                $stmt->execute();

                                                // Actualizamos la tabla CUSTOMERS_OFFERS para marcar las ofertas como usadas
                                                if (isset($_SESSION['ofertasActivas'])) {
                                                    foreach ($_SESSION['ofertasActivas'] as $f) {
                                                        $stmt = $connection->prepare("UPDATE CUSTOMERS_OFFERS SET used = 1 WHERE user_id = ? AND offer_id = ?");
                                                        $stmt->bind_param("ii", $_SESSION['user_id'], $f['offer_id']);
                                                        $stmt->execute();
                                                    }
                                                }
                                            }
                                        }
                                        $_SESSION['compra'] = null;
                                    }
                                }
                                echo "<ul>";  // Comenzamos a mostrar la lista de productos

                                // Recorremos los productos del carrito
                                if (isset($_SESSION['compra'])) {
                                    foreach ($_SESSION['compra'] as $p) {
                                        $descuento = $p['precio'] * $p['cantidad'];  // Calculamos el precio base del producto

                                        // Verificamos si hay alguna oferta para este producto
                                        if (isset($_SESSION['ofertasActivas'])) {
                                            foreach ($_SESSION['ofertasActivas'] as $f) {
                                                if ($f['nombre'] == $p['nombre']) {
                                                    $descuento *= (1 - $f['discount'] / 100);  // Aplicamos el descuento si existe una oferta
                                                }
                                            }
                                        }

                                        // Mostramos el nombre del producto y su precio con descuento
                                        echo "<li>" . $p['nombre'] . " Precio: " . $descuento . " €";
                                        if (!empty($p['lista_ingredientes']) && is_array($p['lista_ingredientes'])) {
                                            echo "<ul>";
                                            foreach ($p['lista_ingredientes'] as $ingrediente) {
                                                echo "<li>" . $ingrediente[0] ."  ". $ingrediente[2]  ."  " . $ingrediente[1] . "</li>";
                                            }
                                            echo "</ul>";
                                        }
                                        echo "</li>";
                                        $v_total += $descuento;
                                    }
                                }
                                echo "</ul>";  // Terminamos la lista de productos
                            } else {
                                // Si no hay productos en el carrito, mostramos una lista vacía
                                echo "<ul></ul>";
                            }

                            // Creamos el formulario para confirmar la compra
                            echo "<form action=\"confirmar_pedido.php\" method=\"POST\">";
                            echo "Precio total: " . $v_total . " €\t<input type=\"submit\" name=\"Confirmar\" value=\"Confirmar\"/>";
                            echo "</form>";
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