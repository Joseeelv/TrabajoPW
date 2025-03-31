<?php
// Definir los elementos del menú según el tipo de usuario
if (isset($_SESSION['user_type'])) {

    switch ($_SESSION['user_type']) {
        case 'admin':
            $menuItems = [
                'Inicio' => './admin.php',
                'Empleados' => './employees.php',
                'Contratar' => './contratar.php',
                'Despedir' => './despedir.php',
                'Perfil' => './perfil.php',
                'Cerrar Sesión' => './logout.php'
            ];
            break;
        case 'manager':
            $menuItems = [
                'Inicio' => './manager_index.php',
                'Reabastecer' => './manager_replineshment.php',
                'Transacciones' => './transactions.php',
                'Perfil' => './perfil.php',
                'Cerrar Sesión' => './logout.php'
            ];
            break;
        case 'customer':
            $menuItems = [
                'Inicio' => './dashboard.php',
                'Ofertas' => './ofertas.php',
                'Carta' => './Menu.php',
                'Carrito' => './Carrito.php',
                'Perfil' => './perfil.php',
                'Cerrar Sesión' => './logout.php'

            ];
            break;
    }
} else {
    $menuItems = [
        'Inicio' => '../index.php',
        'Carta' => './Menu.php',
        'Contacto' => './contact.php',
        'Iniciar Sesión' => './login.php',
        'Registrarse' => './register.php'

    ];
}

// Generar el HTML de la navbar
?>
<header>
    <nav class="navbar">
        <h1>DÖNER KEBAB SOCIETY</h1>
        <?php
        foreach ($menuItems as $label => $url) {
            echo "<a href=\"$url\" class=\"menu-link\">$label</a>";
        }
        ?>
    </nav>
</header>