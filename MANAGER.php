<html>

<head>
    <title>Manager Dashboard</title>
</head>

<body>
    <h1>Manager Dashboard</h1>

    <?php
    require_once('./functions/.configDB.php');
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }
    // Obtener ingredientes 
    $stmt = $connection->prepare("SELECT * FROM INGREDIENTS");
    $stmt->execute();
    $result_ing = $stmt->get_result();

    // Obtener productos con stock
    $stmt = $connection->prepare("SELECT * FROM PRODUCTS WHERE CATEGORY = 'Drink' or CATEGORY = 'Dessert'");
    $stmt->execute();
    $result_prod = $stmt->get_result();
    ?>
    <h2>Stock de ingredientes</h2>
    <table border='1'>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Precio unitario</th>
            <th>Stock</th>
            <th>Cantidad</th>
            <th>Pedir</th>
        </tr>
        <?php
        if ($result_ing->num_rows > 0) {
            while ($row = $result_ing->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["ingredient_id"] . "</td>";
                echo "<td>" . $row["ingredient_name"] . "</td>";
                echo "<td>" . $row["cost"] . "</td>";
                echo "<td>" . $row["stock"] . "</td>";
                echo "<td>";
                ?>
                <form action='./functions/order_ingredients.php' method='POST'>
                    <input type='hidden' name='ingredient_id' value='<?php echo $row["ingredient_id"]; ?>'>
                    <input type='hidden' name='cost' value='<?php echo $row["cost"]; ?>'>
                    <input type='number' name='quantity' value='10' min='1' required>
                    </td>
                    <td>
                        <input type='submit' value='+'>
                </form>
                </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='6'>No hay ingredientes en stock</td></tr>";
        }

        echo "</table>";
        ?>
        <h2>Stock de productos</h2>
        <table border='1'>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio unitario</th>
                <th>Stock</th>
                <th>Cantidad</th>
                <th>Pedir</th>
            </tr>
            <?php
            if ($result_prod->num_rows > 0) {
                while ($row = $result_prod->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["product_id"] . "</td>";
                    echo "<td>" . $row["product_name"] . "</td>";
                    echo "<td>" . $row["cost"] . "</td>";
                    echo "<td>" . $row["stock"] . "</td>";
                    echo "<td>";
                    ?>
                    <form action='./functions/order_ingredients.php' method='POST'>
                        <input type='hidden' name='product_id' value='<?php echo $row["product_id"]; ?>'>
                        <input type='hidden' name='cost' value='<?php echo $row["cost"]; ?>'>
                        <input type='number' name='quantity' value='10' min='1' required>
                        </td>
                        <td>
                            <input type='submit' value='+'>
                    </form>
                    </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='6'>No hay productos en stock</td></tr>";
            }
    
            echo "</table>";

        $connection->close();
        ?>
</body>

</html>