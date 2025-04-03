<?php
session_start();

// Habilitar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$connection = include('./conexion.php');

try {
    if (!$connection) {
        throw new Exception("Error de conexión a la base de datos");
    }

    if (!isset($_SESSION['compra']) || empty($_SESSION['compra'])) {
        header("Location: carrito.php");
        exit();
    }

    $v_total = 0;  // Total de la compra

    // Obtener el total con descuentos
    foreach ($_SESSION['compra'] as $p) {
        $precio_final = $p['precio'] * $p['cantidad'];

        // Aplicar descuento si hay oferta
        if (!empty($_SESSION['ofertasActivas'])) {
            foreach ($_SESSION['ofertasActivas'] as $f) {
                if ($f['nombre'] == $p['nombre']) {
                    $precio_final *= (1 - $f['discount'] / 100);
                }
            }
        }
        $v_total += $precio_final;
    }

    // Actualizar puntos del usuario
    $puntos = (int)($v_total * 10);
    $stmt = $connection->prepare("UPDATE CUSTOMERS SET points = points + ? WHERE user_id = ?");
    $stmt->bind_param("ii", $puntos, $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['puntos'] += $puntos;

    // Crear la orden
    $stmt = $connection->prepare("INSERT INTO ORDERS(user_id, order_date, order_status) VALUES (?, ?, ?)");
    $order_date = date('Y-m-d');
    $order_status = 'pendiente';
    $stmt->bind_param("iss", $_SESSION['user_id'], $order_date, $order_status);
    $stmt->execute();
    $order_id = $connection->insert_id;

    // Insertar los productos en ORDER_ITEMS
    foreach ($_SESSION['compra'] as $p) {
        $precio_final = $p['precio'] * $p['cantidad'];

        if (!empty($_SESSION['ofertasActivas'])) {
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
        if (in_array($p['category'], ['DRINK', 'DESSERT'])) {
            $stmt = $connection->prepare("UPDATE Products SET stock = stock - 1 WHERE product_id = ?");
            $stmt->bind_param("i", $p['id']);
            $stmt->execute();
        } else {
            // Insertar ingredientes eliminados/añadidos
            foreach ($p['lista_ingredientes'] as $ingrediente) {
                $ing_id = intval($ingrediente['id']);
                $cantidad = intval($ingrediente['cantidad']);

                if ($cantidad < 0) {
                    throw new Exception("Error: Cantidad de ingrediente inválida (ID: $ing_id, cantidad: $cantidad).");
                }

                // Verificar existencia de ingrediente
                $stmt_check = $connection->prepare("SELECT COUNT(*) FROM INGREDIENTS WHERE ingredient_id = ?");
                $stmt_check->bind_param("i", $ing_id);
                $stmt_check->execute();
                $stmt_check->bind_result($ing_exists);
                $stmt_check->fetch();
                $stmt_check->close();
                if (!$ing_exists) {
                    throw new Exception("Error: Ingrediente con ID $ing_id no existe.");
                }

                // Insertar en ORDER_ITEMS_INGREDIENTS
                $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS_INGREDIENTS(order_item_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $order_item_id, $ing_id, $cantidad);
                $stmt->execute();

                // Actualizar stock del ingrediente
                $stmt = $connection->prepare("UPDATE INGREDIENTS SET stock = stock - ? WHERE ingredient_id = ?");
                $stmt->bind_param("ii", $cantidad, $ing_id);
                $stmt->execute();
            }
        }
        // insertar en transacciones
        $stmt = $connection->prepare("INSERT INTO TRANSACTIONS(order_id, replenishment_id, transaction_money) VALUES (?, NULL, ?)");
        $transaction_money = $precio_final;
        $stmt->bind_param("id", $order_id, $transaction_money);
        $stmt->execute();
        $transaction_id = $connection->insert_id;

    }

    // Marcar ofertas como usadas
    if (!empty($_SESSION['ofertasActivas'])) {
        foreach ($_SESSION['ofertasActivas'] as $f) {
            $stmt = $connection->prepare("UPDATE CUSTOMERS_OFFERS SET used = 1 WHERE user_id = ? AND offer_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $f['offer_id']);
            $stmt->execute();
        }
    }

    // Vaciar el carrito después de la compra
    $_SESSION['compra'] = [];

    // Redirigir al carrito con mensaje de éxito
    header("Location: pedido_confirmado.php");
    exit();
} catch (Exception $e) {
    error_log("Error en la compra: " . $e->getMessage());
    die("Error en la compra: " . $e->getMessage());
}