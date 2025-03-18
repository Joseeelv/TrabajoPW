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

    $stmt = $connection->prepare("SELECT * FROM INGREDIENTS");
    $stmt->execute();
    $result = $stmt->get_result();
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
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["ingredient_id"] . "</td>";
                echo "<td>" . $row["ingredient_name"] . "</td>";
                echo "<td>" . $row["cost"] . "</td>";
                echo "<td>" . $row["stock"] . "</td>";
                echo "<td>";
                ?>
                <form action='./functions/order_ingredients.php' method='POST'>
                    <input type='hidden' name='ingredient_id' value='<?php echo $row["ingredient_id"]; ?>'>
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

        $connection->close();
        ?>
</body>

</html>