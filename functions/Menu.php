<?php
session_start();

try {
    // Establish a connection to the MySQL database using mysqli
    $connection = require_once('./conexion.php');
    if (!$connection) {
        die("Error en la conexión a la base de datos: " . mysqli_connect_error());
    }

    if (!isset($_SESSION['categorias'])) {
        $query = "SELECT PRODUCTS.category as cat FROM PRODUCTS group by PRODUCTS.category";
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $_SESSION['categorias'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    if (isset($_POST['category'])) {
        $category = $_POST['category'];
    } else {$category = 'Ninguna';}
    
    if ($category != "Ninguna") {
        $query = "SELECT PRODUCTS.product_id as id, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS where PRODUCTS.category = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $query = "SELECT PRODUCTS.product_id as id, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS";
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    // If a D_Error exception is thrown, redirect to the 500 error page
    header("Location: 500.php");
}
?>

<html>

<head>
    <title>Menu</title>
    <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>

<body>
    <?php include('./navbar.php'); ?>
    <main>
        <aside class="sidebar">
            <ul>
                <form method="POST"><input type="hidden" name="category" value="Ninguna"><button
                        type="submit">Ninguna</button></form>
                <?php
                foreach ($_SESSION['categorias'] as $c) {
                    $categoryName = htmlspecialchars($c['cat'], ENT_QUOTES, 'UTF-8'); // Sanitize category name
                    echo "
                            <form method=\"POST\">
                                <input type=\"hidden\" name=\"category\" value=\"" . $categoryName . "\">
                                <button type=\"submit\">" . $categoryName . "</button>
                            </form>
                          ";
                }
                ?>
            </ul>
        </aside>
        <?php
        echo "<ul class=\"container-productos\">";

        // Loop through each row in the result set
        foreach ($_SESSION['menu'] as $f) {
            // Ensure that the image and product name are properly displayed
            $productName = htmlspecialchars($f["nombre"], ENT_QUOTES, 'UTF-8');  // Prevent XSS by escaping characters
            $productImg = htmlspecialchars("../assets/images/productos/" . $f["img"], ENT_QUOTES, 'UTF-8'); // Sanitize image URL
            $id = htmlspecialchars($f["id"], ENT_QUOTES, 'UTF-8'); // Sanitize product ID
            if (isset($_SESSION['user_id'])) {

                // Create the list item with an image, product name, and link to 'producto.php' with a product ID
                echo "<form method=\"POST\" action=\"producto.php\">
                        <input type=\"hidden\" name=\"idProdSelecCarta\" value=\"" . $id . "\" />
                        <button type=\"submit\" class=\"container-producto\">
                            <img class=\"imagen-producto\" src=\"" . $productImg . "\" alt=\"" . $productName . "\" />
                            <span>" . $productName . "</span>
                        </button>
                    </form>";
            } else {
                // Create the list item with an image, product name, and link to 'producto.php' with a product ID
                echo "<button  onclick=\"window.location.href='./login.php'\" class=\"container-producto\">
                    <img class=\"imagen-producto\" src=\"" . $productImg . "\" alt=\"" . $productName . "\" />
                    <span>" . $productName . "</span>
                    </button>";
            }
        }
        ?>
        </ul>

    </main>
    <?php include('./footer.php'); ?>
</body>


</html>