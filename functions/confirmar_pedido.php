<?php
session_start();

$connection = include('./conexion.php');
// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);



try {
    // Verificar si el carrito está vacío
    if (!isset($_SESSION['compra']) || empty($_SESSION['compra'])) {
        header("Location: carrito.php");
        exit();
    }

    $v_total = 0;  // Total de la compra

    // Obtener el total con descuentos
    foreach ($_SESSION['compra'] as $p) {
        $precio_base = $p['precio'] * $p['cantidad'];
        $precio_final = $precio_base;

        // Aplicar descuento si hay oferta
        if (isset($_SESSION['ofertasActivas'])) {
            foreach ($_SESSION['ofertasActivas'] as $f) {
                if ($f['nombre'] == $p['nombre']) {
                    $precio_final *= (1 - $f['discount'] / 100);
                }
            }
        }
        $v_total += $precio_final;
    }

    // Actualizar puntos del usuario
    $puntos = ((int)($v_total / 10)) * 100;
    $stmt = $connection->prepare("UPDATE CUSTOMERS SET points = points + ? WHERE user_id = ?");
    $stmt->bind_param("ii", $puntos, $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['puntos'] += $puntos; // Actualizar puntos en la sesión

    // Crear la orden
    $stmt = $connection->prepare("INSERT INTO ORDERS(user_id, order_date, order_status) VALUES (?, ?, ?)");
    $order_date = date('Y-m-d');
    $order_status = 'pendiente';
    $stmt->bind_param("iss", $_SESSION['user_id'], $order_date, $order_status);
    $stmt->execute();
    $order_id = $connection->insert_id;

    // Insertar los productos en ORDER_ITEMS
    foreach ($_SESSION['compra'] as $p) {
        $precio_base = $p['precio'] * $p['cantidad'];
        $precio_final = $precio_base;

        // Aplicar descuento
        if (isset($_SESSION['ofertasActivas'])) {
            foreach ($_SESSION['ofertasActivas'] as $f) {
                if ($f['nombre'] == $p['nombre']) {
                    $precio_final *= (1 - $f['discount'] / 100);
                }
            }
        }

        $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS(order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $p['id'], $p['cantidad'], $precio_final);
        $stmt->execute();
        $order_item_id = $connection->insert_id;

        // Si es bebida o postre, actualizar stock del producto
        if ($p['category'] == 'DRINK' || $p['category'] == 'DESSERT') {
            $stmt = $connection->prepare("UPDATE Products SET stock = stock - 1 WHERE product_id = ?");
            $stmt->bind_param("i", $p['id']);
            $stmt->execute();
        } else {
            // Insertar ingredientes eliminados/añadidos en ORDER_ITEMS_INGREDIENTS
            foreach ($p['lista_ingredientes'] as $ingrediente) {
                $ing_id = $ingrediente[0];
                $cantidad = $ingrediente[1];
                $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS_INGREDIENTS(order_item_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $order_item_id, $ing_id, $cantidad);
                $stmt->execute();

                // Actualizar stock del ingrediente
                $stmt = $connection->prepare("UPDATE Ingredients SET stock = stock - ? WHERE ingredient_id = ?");
                $stmt->bind_param("ii", $cantidad, $ing_id);
                $stmt->execute();
            }
        }
    }

    // Marcar ofertas como usadas
    if (isset($_SESSION['ofertasActivas'])) {
        foreach ($_SESSION['ofertasActivas'] as $f) {
            $stmt = $connection->prepare("UPDATE CUSTOMERS_OFFERS SET used = 1 WHERE user_id = ? AND offer_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $f['offer_id']);
            $stmt->execute();
        }
    }

    // Vaciar el carrito después de la compra
    $_SESSION['compra'] = [];

    // Redirigir al carrito con mensaje de éxito
    header("Location: carrito.php");
    exit();

}catch (Exception $e) {
    error_log("Error en la compra: " . $e->getMessage()); // Registra el error en logs
    die("Error en la compra: " . $e->getMessage()); // Muestra el error en la pantalla temporalmente
}
?>
