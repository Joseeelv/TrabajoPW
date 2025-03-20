<?php
session_start();

// Definir los elementos del menú según el tipo de usuario
switch ($_SESSION['user_type']) {
    case 'admin':
        $menuItems = [
            'Inicio' => '../functions/admin.php',
            'Cerrar Sesión' => '../functions/logout.php'
        ];
        break;
    case 'manager':
        $menuItems = [
            'Inicio' => '../functions/manager.php',
            'Cerrar Sesión' => '../functions/logout.php'
        ];
        break;
    case 'customer':
        $menuItems = [
            'Inicio' => '../functions/dashboard.php',
            'Carta' => '',
            'Perfil' => '../functions/perfil.php',
            'Cerrar Sesión' => '../functions/logout.php'
        ];
        break;
}

// Generar el HTML de la navbar
?>
<link rel="stylesheet" href="../assets/styles.css">
<header>
<nav class="navbar">
    <?php
    foreach ($menuItems as $label => $url) {
        echo "<a href=\"$url\" class=\"menu-link\">$label</a>";
    }

    ?>
</nav>
</header>

