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
                            // Obtener ofertas activas si no están en sesión
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
                            $v_total = 0; // Total de la compra

                            echo "<h2>Ofertas activas:</h2>";
                            foreach ($_SESSION['ofertasActivas'] as $f) {
                                echo "<ul><li>" . htmlspecialchars($f['of_name']) . "</li></ul>";
                            }

                            echo "<h2>Productos en el carrito:</h2>";

                            if (isset($_SESSION['compra']) && !empty($_SESSION['compra'])) {
                                echo "<ul>";

                                // Ordenamos los productos por nombre para que estén agrupados
                                usort($_SESSION['compra'], function ($a, $b) {
                                    return strcmp($a['nombre'], $b['nombre']);
                                });

                                foreach ($_SESSION['compra'] as $p) {
                                    $precio_base = $p['precio'] * $p['cantidad'];
                                    $precio_final = $precio_base;

                                    // Aplicamos descuentos si hay ofertas
                                    foreach ($_SESSION['ofertasActivas'] as $f) {
                                        if ($f['nombre'] == $p['nombre']) {
                                            $precio_final *= (1 - $f['discount'] / 100);
                                        }
                                    }

                                    echo "<li><strong>" . htmlspecialchars($p['nombre']) . "</strong> - Precio: " . number_format($precio_final, 2) . " €";

                                    if (!empty($p['lista_ingredientes'])) {
                                        echo "<ul>";
                                        foreach ($p['lista_ingredientes'] as $ingrediente) {
                                            echo "<li>" . htmlspecialchars($ingrediente) . "</li>";
                                        }
                                        echo "</ul>";
                                    }
                                    echo "</li>";

                                    $v_total += $precio_final;
                                }
                                echo "</ul>";

                                // Formulario de confirmación de compra
                                echo "<form action=\"confirmar_pedido.php\" method=\"POST\">";
                                echo "Precio total: " . number_format($v_total, 2) . " € ";
                                echo "<input type=\"submit\" name=\"Confirmar\" value=\"Confirmar\"/>";
                                echo "</form>";
                            } else {
                                echo "<p>Tu carrito está vacío.</p>";
                            }
                        } catch (Exception $e) {
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
</html>
