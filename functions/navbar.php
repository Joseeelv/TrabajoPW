<?php
// Definir los elementos del menú según el tipo de usuario
switch ($_SESSION['user_type']) {
    case 'admin':
        $menuItems = [
            'Inicio' => './admin.php',
            'Cerrar Sesión' => './logout.php'
        ];
        break;
    case 'manager':
        $menuItems = [
            'Inicio' => './manager_index.php',
            'Reabastecer' => './manager_replineshment.php',
            'Transacciones' => './manager_transactions.php',
            'Cerrar Sesión' => './logout.php'
        ];
        break;
    case 'customer':
        $menuItems = [
            'Inicio' => './dashboard.php',
            'Carta' => '',
            'Perfil' => './perfil.php',
            'Cerrar Sesión' => './logout.php'
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

