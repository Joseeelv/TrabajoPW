<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Pedidos</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/pedidos.css">
</head>

<body>
  <?php
  session_start();
  $conn = include('./conexion.php');

  try {
    // Obtener pedidos del usuario
    $query = "SELECT * FROM ORDERS WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['pedidos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  ?>

    <?php include("./navbar.php"); ?>

    <main class="main-container">
      <section class="orders-container">
        <h1>Mis Pedidos</h1>
        <ul class="orders-list">
          <?php foreach ($_SESSION['pedidos'] as $pedido): ?>
            <li class="order-item">
              <h2>Pedido del <?php echo $pedido["order_date"]; ?></h2>
              <ul class="products-list">
                <?php
                $query = "SELECT PRODUCTS.product_name as n, ORDER_ITEMS.quantity as q, ORDER_ITEMS.price as p 
                                      FROM ORDER_ITEMS 
                                      JOIN PRODUCTS ON ORDER_ITEMS.product_id = PRODUCTS.product_id 
                                      WHERE order_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $pedido['order_id']);
                $stmt->execute();
                $_SESSION['productos_pedido'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                <?php foreach ($_SESSION['productos_pedido'] as $prod): ?>
                  <li class="product-item">
                    Producto: <?php echo $prod['n']; ?> | Cantidad: <?php echo $prod['q']; ?> | Precio: <?php echo $prod['p']; ?>€
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </main>

    <?php include("./footer.php"); ?>

  <?php
  } catch (Exception $e) {
    header("Location: 500.php");
  }
  ?>
</body>

</html>