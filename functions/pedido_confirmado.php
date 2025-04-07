<html lang="es">
<?php
session_start();
?>

<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/carrito.css">
    <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
</head>

<body>
    <?php include('./navbar.php'); ?>
    <main>
        <h1>Pedido Confirmado</h1>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p>Su pedido ha sido confirmado. Gracias por su compra.</p>
                    <img src="../assets/images/logo/logo.png" alt="Logo DKS" class="logo">
                    <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">Volver a la página principal</button>
                </div>
            </div>
        </div>
    </main>
    <?php include('./footer.php'); ?>
</body>

</html>