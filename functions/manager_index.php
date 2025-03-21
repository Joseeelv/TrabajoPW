<?php
    session_start();
?>

<html>

<head>
    <title>Manager</title>

</head>

<body>
    <?php
    include('manager_header.php');
    ?>
    <h1> Este es el dashboard del manager </h1>
    <a href="./manager_replineshment.php">Reabastecer productos</a>
    <a href="./manager_transactions.php">Ver transacciones</a>

</body>

</html>