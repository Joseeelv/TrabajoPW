<?php
try {
    // Check if there is a session variable 'compra' (shopping cart)
    if (isset($_SESSION['compra'])) {
        $v_total = 0;  // Initialize the total amount to 0
        echo "<ul>";  // Start an unordered list to display the cart items

        // Loop through the shopping cart (stored in $_SESSION['compra'])
        foreach ($_SESSION['compra'] as $p) {
            // Loop through the available offers (stored in $_SESSION["Ofertas"])
            foreach ($_SESSION["Ofertas"] as $f) {
                // Check if the offer matches the product in the cart
                if ($f["nombre"] == $p["nombre"]) {
                    $descuento = $f["discount"];  // Apply the discount from the offer
                }
            }
            // Display product name, price, and discount in the list
            echo "<li>" . $p["nombre"] . " " . $p["precio"] * $descuento ."</li>";

            // Add the price of the product to the total amount
            $v_total += $p["precio"] * $descuento;
        }
        echo "</ul>";  // End the unordered list
    } else {
        // If the shopping cart is empty, display an empty list
        echo "<ul></ul>";
    };

    // Create a form to confirm the purchase
    echo "<form action=\"Carrito.php\" method=\"POST\">";
    echo "Price: " . $v_total . "<input type=\"submit\" name=\"Confirmar\" value=1/>";  // Display the total price and submit button
    echo "</form>";

    // If the form is submitted and the user confirms the purchase
    if ($_POST["Confirmar"] == 1) {
        // Create a new database connection
        $conn = new mysqli("localhost", "root", "");
        
        // Prepare a query to update the customer's points based on the total purchase
        $query = "UPDATE CUSTOMERS SET points = points + " . ($v_total / 10) * 100 . " WHERE user_id = " . $_SESSION["USER_ID"];
        
        // Execute the query to update the points in the database
        $conn->query($query);
        
        // Clear the shopping cart in the session after the purchase is confirmed
        $_SESSION['compra'] = null;
    }
} catch (Exception $e) {
    // If a D_Error exception is thrown, redirect to the 500 error page
    header("Location: 500.php");
}
?>
