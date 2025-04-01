<?php
    session_start();
    $_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");
    $_SESSION['user_id'] = 2;

    try{
        // Establish a connection to the MySQL database using mysqli
        $conn = $_SESSION['connection'];

        $query = "SELECT * FROM ORDERS WHERE user_id = ?";
        $stmt = $_SESSION['connection']->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['pedidos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Begin an unordered list (ul) to display products
        echo "<ul>";

        // Loop through each row in the result set
        foreach ($_SESSION['pedidos'] as $pedido){
            $query = "SELECT PRODUCTS.product_name as n, ORDER_ITEMS.quantity as q, ORDER_ITEMS.price as p FROM ORDER_ITEMS JOIN PRODUCTS ON ORDER_ITEMS.product_id = PRODUCTS.product_id WHERE order_id = ?";
            $stmt = $_SESSION['connection']->prepare($query);
            $stmt->bind_param("i", $pedido['order_id']);
            $stmt->execute();
            $_SESSION['productos_pedido'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // For each row, create a list item (li) with an image and product name and discount
            // The image source and product name are pulled from the database results
            echo "<li>". $pedido["order_date"] ."<ul>";
            echo "<li>";
            foreach ($_SESSION['productos_pedido'] as $prod) {
                echo "Producto: ". $prod['n']. " Cantidad: ". $prod['q']. " Precio: ". $prod['p'];
            }
            echo "</li>";
            echo "</ul></li>";
        }

        // Close the unordered list (ul)
        echo "</ul>";
    }catch(Exception $e){
        // If a D_Error exception is thrown, redirect to the 500 error page
        header("Location: 500.php");
    }
?>
