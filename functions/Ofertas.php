<?php
session_start();
$_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");

try {
    // Establish a connection to the MySQL database using mysqli
    $conn = $_SESSION['connection'];

    if (!isset($_SESSION['ofertas'])) {
        $query = "SELECT OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
        $stmt = $_SESSION['connection']->prepare($query);
        $stmt->execute();
        $_SESSION['ofertas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    ?>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../assets/css/styles.css">
        <title>Ofertas</title>
    </head>

    <body>
        <?php include("./navbar.php"); ?>
        <main>
            <?php
            // Begin an unordered list (ul) to display products
            echo "<ul>";

            // Loop through each row in the result set
            foreach ($_SESSION['ofertas'] as $f) {
                // For each row, create a list item (li) with an image and product name and discount
                // The image source and product name are pulled from the database results
                echo "<li><a href=\"producto.php\"><img width=\"100px\"src=../assets/images/productos/" . $f["img"] . " alt=\"\"></a>" . $f["nombre"] . " " . $f["discount"] . "</li>";
            }

            // Close the unordered list (ul)
            echo "</ul>";
} catch (Exception $e) {
    // If a D_Error exception is thrown, redirect to the 500 error page
    header("Location: 500.php");
}
?>
    </main>
    <?php include("./footer.php");
    ?>
</body>

</html>