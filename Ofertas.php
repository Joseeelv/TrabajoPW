<?php
    // Establish a connection to the MySQL database using mysqli
    $conn = new mysqli("localhost", "root", "");

    // SQL query to join OFFERS and PRODUCTS tables and select specific fields
    $query = "SELECT OFFERS.discount as d, PRODUCTS.product_name as n, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";

    // Execute the query and store the result in $consulta
    $consulta = $conn->query($query);

    // Begin an unordered list (ul) to display products
    echo "<ul>";

    // Loop through each row in the result set
    while($f = mysqli_fetch_assoc($consulta)){
        // For each row, create a list item (li) with an image and product name and discount
        // The image source and product name are pulled from the database results
        echo "<li><a header=\"producto.php\"><img src=". $f["img"] ." alt=\"\"></a>". $f["n"] ." ". $f["d"] ."</li>";
    }

    // Close the unordered list (ul)
    echo "</ul>";
?>
