<?php
// Definir los elementos del menú según el tipo de usuario
if (isset($_SESSION['user_type'])) {

    switch ($_SESSION['user_type']) {
        case 'admin':
            $menuItems = [
                'Inicio' => './admin.php',
                'Empleados' => './employees.php',
                'Contratar' => './contratar.php',
                'Perfil' => './perfil.php',
                'Transacciones' => './transactions.php',
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
                'Pedidos Recientes' => './pedidos.php',
                'Perfil' => './perfil.php',
                'Cerrar Sesión' => './logout.php'
            ];
            break;
    }
} else {
    $menuItems = [
        'Inicio' => '../index.php',
        'Carta' => './menu.php',
        'Contacto' => './contact.php',
        'Iniciar Sesión' => './login.php',
        'Registrarse' => './register.php'

    ];
}

// Generar el HTML de la navbar
?>
<header>
    <nav class="navbar">
        <h1 class="navbar-title">DÖNER KEBAB SOCIETY</h1>
        <?php
        foreach ($menuItems as $label => $url) {
            echo "<a href=\"$url\" class=\"menu-link\">$label</a>";
        }

        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'customer') {
            echo "<div id=\"kebabito-container\">";
            echo "<img id=\"kebabito-image\" src=\"../assets/images/logo/DKS.png\" alt=\"Kebabito image\"><span> " . $_SESSION['puntos'] . "</span>";
            echo "</div>";
            $profileImagePath = "../assets/images/perfiles/" . $_SESSION['img_src'];
            if (!file_exists($profileImagePath) || empty($_SESSION['img_src'])) {
                $profileImagePath = "../assets/images/perfiles/default.jpg";
            }
            echo "<img id=\"profile-image\" src=\"$profileImagePath\" alt=\"Profile Image\">";
        }
        ?>
    </nav>
</header>