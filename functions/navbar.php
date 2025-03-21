<?php
// Definir los elementos del menú según el tipo de usuario
if (isset($_SESSION['user_type'])) {

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
                'Perfil' => './perfil.php',
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
} else {
    $menuItems = [
        'Inicio' => '../index.php',
        'Carta' => '',
        'Contacto' => '',
        'Iniciar Sesión' => './login.php',
        'Registrarse' => './register.php'

    ];
}

// Generar el HTML de la navbar
?>
<link rel="stylesheet" href="../assets/css/styles.css">
<header>
    <nav class="navbar">
        <?php
        foreach ($menuItems as $label => $url) {
            echo "<a href=\"$url\" class=\"menu-link\">$label</a>";
        }
        ?>
    </nav>
</header>