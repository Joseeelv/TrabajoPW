<html>

<head>
    <title>Historial de Transacciones</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>

<body>
    <?php
    include('./navbar.php');
    $connection = include('./conexion.php');

    // Consulta para obtener las transacciones con la fecha y el manager asociado
    $query = "
        SELECT 
            T.transaction_id, 
            -- COALESCE selecciona la primera fecha no nula entre la fecha de orden y la de reposición
            COALESCE(O.order_date, R.replenishment_date) AS transaction_date, 
            
            -- COALESCE selecciona el nombre del manager de la orden o reposición
            COALESCE(O.user_id, R.manager_id) AS user_id,

            -- CASE determina si la transacción es una Venta o una Compra
            CASE 
                WHEN T.order_id IS NOT NULL THEN 'Venta' 
                ELSE 'Compra' 
            END AS transaction_type,
            
            -- CASE ajusta el signo del balance según si es reposición (negativo) o venta (positivo)
            CASE 
                WHEN T.replenishment_id IS NOT NULL THEN -T.transaction_money 
                ELSE T.transaction_money 
            END AS balance
        FROM TRANSACTIONS T
        
        -- LEFT JOIN para unir las órdenes y obtener su fecha si existen
        LEFT JOIN ORDERS O ON T.order_id = O.order_id
        
         -- LEFT JOIN para unir los usuarios y obtener su nombre si existen
        LEFT JOIN USERS U ON O.user_id = U.user_id
        

        -- LEFT JOIN para unir las reposiciones y obtener su fecha si existen
        LEFT JOIN REPLENISHMENTS R ON T.replenishment_id = R.replenishment_id
        
        ORDER BY transaction_date DESC;
    ";

    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    // Calcular el resumen de todas las transacciones
    $total_ventas = 0;
    $total_compras = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['balance'] > 0) {
            $total_ventas += $row['balance'];
        } else {
            $total_compras += abs($row['balance']);
        }
    }

    $balance_final = $total_ventas - $total_compras;

    // Reiniciar el puntero del resultado para recorrerlo de nuevo
    $result->data_seek(0);
    ?>

    <h1>Resumen de Transacciones</h1>

    <table border='1'>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Balance (€)</th>
        </tr>
        <!-- Fila de resumen -->
        <tr style="font-weight: bold;">
            <td>Total</td>
            <td>Ventas: <?= number_format($total_ventas, 2) ?>€ | Compras: <?= number_format($total_compras, 2) ?>€</td>
            <td><?= number_format($balance_final, 2) ?>€</td>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["transaction_date"] . "</td>";
                echo "<td>" . $row["transaction_type"] . "</td>";
                echo "<td>" . number_format($row["balance"], 2) . "€</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No hay transacciones registradas.</td></tr>";
        }
        ?>
    </table>

    <?php
    $stmt->close();
    include('./footer.php');
    ?>
</body>

</html>