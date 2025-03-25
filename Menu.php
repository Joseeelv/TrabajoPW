<?php
session_start();
$_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");

try {
    // Establish a connection to the MySQL database using mysqli
    $conn = $_SESSION['connection'];

    if (!isset($_SESSION['categoria'])) {
        $query = "SELECT PRODUCTS.category as cat FROM PRODUCTS group by PRODUCTS.category";
        $stmt = $_SESSION['connection']->prepare($query);
        $stmt->execute();
        $_SESSION['categoria'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    if (!isset($_SESSION['menu']) || isset($_POST['category'])) {
        $category = $_POST['category'];
        if ($category != "Ninguna") {
            $query = "SELECT PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS where PRODUCTS.category = ?";
            $stmt = $_SESSION['connection']->prepare($query);
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $query = "SELECT PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS";
            $stmt = $_SESSION['connection']->prepare($query);
            $stmt->execute();
            $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }

    
} catch (Exception $e) {
    // If a D_Error exception is thrown, redirect to the 500 error page
    header("Location: 500.php");
}
?>

<html>

<head>
    <title>Menu</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>

<body>
    <?php include('./navbar.php'); ?>
    <main>
        <?php echo "<form method=\"POST\">";
        echo "<select name=\"category\" id=\"category\">";
        echo "<option value=Ninguna>Ninguna</option>";
        foreach ($_SESSION['categoria'] as $c) {
            echo "<option value=" . $c['cat'] . ">" . $c['cat'] . "</option>";
        }
        echo "</select>";
        echo "<input type=\"submit\" value=\"Submit\"></form>";
        // Begin an unordered list (ul) to display products
    echo "<ul>";

    // Loop through each row in the result set
    foreach ($_SESSION['menu'] as $f) {
        // Ensure that the image and product name are properly displayed
        $productName = htmlspecialchars($f["nombre"], ENT_QUOTES, 'UTF-8');  // Prevent XSS by escaping characters
        $productImg = htmlspecialchars("../assets/images/productos/" . $f["img"], ENT_QUOTES, 'UTF-8'); // Sanitize image URL

        if(isset($_SESSION['user_id'])){
            // Create the list item with an image, product name, and link to 'producto.php' with a product ID
            echo "<li><a href=\"producto.php\">
                        <img style='width:100px;height:100px;' src=\"" . $productImg . "\" alt=\"" . $productName . "\" />
                        <span>" . $productName . "</span></a></li>";
        }else{
            // Create the list item with an image, product name, and link to 'producto.php' with a product ID
            echo "<li><a href=\"login.php\">
                    <img style='width:100px;height:100px;' src=\"" . $productImg . "\" alt=\"" . $productName . "\" />
                    <span>" . $productName . "</span></a></li>";
        }
    }

    // Close the unordered list (ul)
    echo "</ul>";
        ?>
    </main>
    <?php include('./footer.php'); ?>

</html>