<?php
session_start();

// Habilitar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$connection = include('./conexion.php');

try {
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
    $puntos = (int)($v_total * 10);
    $stmt = $connection->prepare("UPDATE CUSTOMERS SET points = points + ? WHERE user_id = ?");
    $stmt->bind_param("ii", $puntos, $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['puntos'] += $puntos; // Actualizar puntos en la sesión

    // Crear la orden
    $stmt = $connection->prepare("INSERT INTO ORDERS(user_id, order_date, order_status) VALUES (?, ?, ?)");
    $order_date = date('Y-m-d');
    $order_status = 'Pending';
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
                // Insertar ingredientes eliminados/añadidos en ORDER_ITEMS_INGREDIENTS
                $ing_id = intval($ingrediente['id']);
                $cantidad = intval($ingrediente['cantidad']);

                // Verificar que el ingrediente exista en INGREDIENTS
                $stmt_check_ing = $connection->prepare("SELECT COUNT(*) FROM INGREDIENTS WHERE ingredient_id = ?");
                $stmt_check_ing->bind_param("i", $ing_id);
                $stmt_check_ing->execute();
                $stmt_check_ing->bind_result($ing_exists);
                $stmt_check_ing->fetch();
                $stmt_check_ing->close();

                if ($ing_exists == 0) {
                    error_log("Error: Ingrediente con ID $ing_id no existe.");
                    die("Error: Ingrediente con ID $ing_id no existe.");
                }

                // Verificar que el order_item_id existe en ORDER_ITEMS
                $stmt_check_item = $connection->prepare("SELECT COUNT(*) FROM ORDER_ITEMS WHERE order_item_id = ?");
                $stmt_check_item->bind_param("i", $order_item_id);
                $stmt_check_item->execute();
                $stmt_check_item->bind_result($item_exists);
                $stmt_check_item->fetch();
                $stmt_check_item->close();

                if ($item_exists == 0) {
                    error_log("Error: ORDER_ITEM_ID $order_item_id no existe.");
                    die("Error: ORDER_ITEM_ID $order_item_id no existe.");
                }

                // Verificar que cantidad sea mayor que 0
                if ($cantidad < 0) {
                    error_log("Error: Cantidad de ingrediente inválida (ID: $ing_id, cantidad: $cantidad).");
                    die("Error: Cantidad de ingrediente inválida.");
                }

                // Insertar en ORDER_ITEMS_INGREDIENTS
                if (isset($order_item_id, $ing_id, $cantidad) && $order_item_id > 0 && $ing_id > 0 && $cantidad >= 0) {
                    $stmt = $connection->prepare("INSERT INTO ORDER_ITEMS_INGREDIENTS(order_item_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $order_item_id, $ing_id, $cantidad);
                    $stmt->execute();
                } else {
                    error_log("Error: Invalid data for ORDER_ITEMS_INGREDIENTS ");
                    die("Error: Invalid data for ORDER_ITEMS_INGREDIENTS. (order_item_id: $order_item_id, ingredient_id: $ing_id, quantity: $cantidad).");
                }

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
    header("Location: carrito.php?success=1");
    exit();
} catch (Exception $e) {
    header("Location: 500.php");
    exit();
}
