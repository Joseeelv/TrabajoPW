<html>

<head>
    <title>Historial de Transacciones</title>
</head>

<body>
    <?php
    include('./manager_header.php');
    session_start();
    require_once('.configDB.php');

    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$connection) {
        die("Error de conexión: " . mysqli_connect_error());
    }

    // Consulta para obtener las transacciones con la fecha adecuada
    $query = "
        SELECT 
            T.transaction_id, 
            COALESCE(O.order_date, R.replenishment_date) AS transaction_date, 
            CASE 
                WHEN T.order_id IS NOT NULL THEN 'Venta' 
                ELSE 'Compra' 
            END AS transaction_type,
            CASE 
                WHEN T.replenishment_id IS NOT NULL THEN -T.transaction_money 
                ELSE T.transaction_money 
            END AS balance
        FROM TRANSACTIONS T
        LEFT JOIN ORDERS O ON T.order_id = O.order_id
        LEFT JOIN REPLENISHMENTS R ON T.replenishment_id = R.replenishment_id
        ORDER BY transaction_date DESC;
    ";

    $result = mysqli_query($connection, $query);

    // Calcular el resumen de todas las transacciones
    $total_ventas = 0;
    $total_compras = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['balance'] > 0) {
            $total_ventas += $row['balance'];
        } else {
            $total_compras += abs($row['balance']);
        }
    }

    $balance_final = $total_ventas - $total_compras;

    // Reiniciar el puntero del resultado para recorrerlo de nuevo
    mysqli_data_seek($result, 0);
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
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
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
    mysqli_close($connection);
    ?>
</body>

</html>