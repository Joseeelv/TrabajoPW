<?php session_start(); ?>
<html>

<head>
    <title>Manager</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/manager.css">

</head>

<body>
    <?php
    include('./navbar.php');
    ?>
    <main>
        <h1> Bienvenido, <?php echo $_SESSION['username'];
        ?> a su menú de manager.</h1>
        <img src="../assets/images/perfiles/<?php echo $_SESSION["user_img"]; ?>" alt="Imagen de perfil"
            id="profile-image">
        <h2>¿Qué desea hacer?</h2>
        <div id="manager-options">
            <a href="./manager_replineshment.php">Reabastecer productos</a>
            <a href="./manager_transactions.php">Ver transacciones</a>
            <a href="./perfil.php">Ver perfil</a>
        </div>
    </main>
    <?php include('./footer.php'); ?>
</body>

</html>