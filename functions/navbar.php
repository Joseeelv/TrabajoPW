<?php
session_start();

// Definir los elementos del menú según el tipo de usuario
switch ($_SESSION['user_type']) {
    case 'admin':
        $menuItems = [
            'Inicio' => '/functions/admin.php',
        ];
        break;
    case 'manager':
        $menuItems = [
            'Incio' => '/functions/manager.php',
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
echo '<nav><ul>';
foreach ($menuItems as $label => $url) {
    echo "<li><a href=\"$url\">$label</a></li>";
}
echo '</ul></nav>';
?>
