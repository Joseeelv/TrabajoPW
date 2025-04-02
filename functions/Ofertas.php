<?php
session_start();
$_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");


$conn = $_SESSION['connection'];

try {
    $query = "SELECT OFFERS.offer_text as of_name, OFFERS.offer_id as id, OFFERS.cost as coronas, OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
    $stmt = $_SESSION['connection']->prepare($query);
    $stmt->execute();
    $_SESSION['ofertas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    ?>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../assets/css/styles.css">
        <link rel="stylesheet" href="../assets/css/ofertas.css">
        <title>Ofertas</title>
    </head>

    <body>
        <?php include("./navbar.php"); ?>
        <main>
            <?php
            echo "<ul>";

            foreach ($_SESSION['ofertas'] as $f) {
                $query = "SELECT * FROM CUSTOMERS_OFFERS WHERE user_id = ? AND offer_id = ?";
                $stmt = $_SESSION['connection']->prepare($query);
                $stmt->bind_param("ii", $_SESSION['user_id'], $f['id']);
                $stmt->execute();
                $_SESSION['Aceptada'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $mensaje = ""; // Variable para almacenar el mensaje de error o éxito
        
                if (isset($_POST['Oferta']) && $_POST['Oferta'] == $f['id'] && empty($_SESSION['Aceptada'])) {
                    if ($_SESSION['points'] >= $f['coronas']) {
                        $query = "INSERT INTO CUSTOMERS_OFFERS(user_id,offer_id,activation_date) values(?,?,?)";
                        $stmt = $_SESSION['connection']->prepare($query);
                        $fecha = date('Y-m-d');
                        $stmt->bind_param("iis", $_SESSION['user_id'], $f['id'], $fecha);
                        $stmt->execute();
                        $_SESSION['points'] -= $f['coronas']; // Restar el coste de la oferta
                        $_SESSION['Aceptada'] = True;
                        // guardar los puntos del usuario de la session a la base de datos
                        if (isset($_SESSION['user_id'])) {
                            $connection = $_SESSION['connection'];
                            $stmt = $connection->prepare("UPDATE CUSTOMERS SET points = ? WHERE user_id = ?");
                            $stmt->bind_param("ii", $_SESSION['points'], $_SESSION['user_id']);
                            $stmt->execute();
                            $stmt->close();
                        }
                        $mensaje = "Oferta activada correctamente.";
                    } else {
                        $mensaje = "No tienes suficientes puntos para activar esta oferta.";
                    }
                }

                echo "<li>
                        <form method=\"POST\">
                        <input type=\"hidden\" name=\"Oferta\" value=\"" . $f['id'] . "\">
                        <input type=\"image\" width=\"100px\"src=../assets/images/productos/" . $f["img"] . " alt=\"\">
                        </form>";

                ?>
                <?php if (!empty($_SESSION['Aceptada'])) { ?>
                    <p>Oferta: <?= $f["nombre"] ?></p>
                    <p>Precio: <?= $f["coronas"] ?><img src='../assets/images/logo/DKS.png' alt='DKS Logo' width='20px'></p>
                    <p>Descuento: <?= $f["discount"] ?>%</p>
                    <p>Activa</p>
                <?php } else { ?>
                    <p>Oferta: <?= $f["nombre"] ?></p>
                    <p>Precio: <?= $f["coronas"] ?><img src='../assets/images/logo/DKS.png' alt='DKS Logo' width='20px'></p>
                    <p>Descuento: <?= $f["discount"] ?>%</p>
                    <p>No Activa</p>
                <?php } ?>
                <?php if (!empty($mensaje)) { ?>
                    <p><?= $mensaje ?></p>
                <?php } ?>
                </li>
            <?php } ?>

            <?php
            echo "</ul>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} ?>
    </main>
    <?php include("./footer.php"); ?>
</body>

</html>