<?php
    session_start();
    $image = "../assets/images/perfiles/" . $_SESSION['img_src'] ?? '../assets/images/perfiles/default.jpg'; // Imagen por defecto si no hay imagen
?>


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
         <img id="profile-image" src="<?php echo htmlspecialchars($image); ?>" alt="ImagenUser">
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