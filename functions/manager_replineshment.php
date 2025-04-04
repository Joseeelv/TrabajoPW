<?php session_start(); ?>

<html>

<head>
    <title>Manager Replineshment</title>
    <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/reabastecer.css">
</head>

<body>
    <?php
    include('./navbar.php');

    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']); // Eliminar mensaje después de mostrarlo
    }

    ?>
    <main>
        <h1>Reabastecer productos</h1>
        <?php
        $connection = include('./conexion.php');
        // Obtener ingredientes 
        $stmt = $connection->prepare("SELECT * FROM INGREDIENTS");
        $stmt->execute();
        $result_ing = $stmt->get_result();

        // Obtener productos con stock
        $stmt = $connection->prepare("SELECT * FROM PRODUCTS WHERE CATEGORY = 'Bebida' or CATEGORY = 'Postre'");
        $stmt->execute();
        $result_prod = $stmt->get_result();
        ?>
        <div class="tables">
            <div id="table1">
                <h2>Stock de ingredientes</h2>

                <table>
                    <tr>
                        <th></th>
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
                            echo "<td><img src='../assets/images/ingredientes/" . $row["img_src"] . "' alt='Imagen de " . $row["ingredient_name"] . "'></td>";
                            echo "<td>" . $row["ingredient_name"] . "</td>";
                            echo "<td>" . $row["cost"] . " €</td>";
                            echo "<td>" . $row["stock"] . "</td>";
                            echo "<td>";
                            ?>
                            <form action='replenishment.php' method='POST'>
                                <input type='hidden' name='ingredient_id' value='<?php echo $row["ingredient_id"]; ?>'>
                                <input type='hidden' name='cost' value='<?php echo $row["cost"]; ?>'>
                                <?php $max_items = 999.99 / $row["cost"]; ?>
                                <input type='number' name='quantity' value='10' min='1' max='<?php echo $max_items ?>' required>
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
            </div>
            <div id="table2">
                <h2>Stock de productos</h2>
                <table>
                    <tr>
                        <th></th>
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
                            echo "<td><img src='../assets/images/productos/" . $row["img_src"] . "' alt='Imagen de " . $row["product_name"] . "'></td>";
                            echo "<td>" . $row["product_name"] . "</td>";
                            echo "<td>" . $row["cost"] . " €</td>";
                            echo "<td>" . $row["stock"] . "</td>";
                            echo "<td>";
                            ?>
                            <form action='replenishment.php' method='POST'>
                                <input type='hidden' name='product_id' value='<?php echo $row["product_id"]; ?>'>
                                <input type='hidden' name='cost' value='<?php echo $row["cost"]; ?>'>
                                <?php $max_items = 999.99 / $row["cost"]; ?>
                                <input type='number' name='quantity' value='10' min='1' max='<?php echo $max_items ?>' required>
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
            </div>
        </div>
    </main>
    <?php
    include('footer.php');
    ?>

</body>

</html>