<?php session_start(); ?>
<html>

<head>
    <title>Manager</title>
    <link rel="stylesheet" href="../assets/styles.css">

</head>

<body>
    <?php
    include('./navbar.php');
    ?>
    <h1> Este es el dashboard del manager </h1>
    <a href="./manager_replineshment.php">Reabastecer productos</a>
    <a href="./manager_transactions.php">Ver transacciones</a>
    <?php include('./footer.php'); ?>
</body>

</html>