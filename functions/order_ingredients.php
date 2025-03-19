<?php
require_once('.configDB.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["ingredient_id"]) || isset($_POST["product_id"])) && isset($_POST["quantity"])) {
    if (isset($_POST["ingredient_id"]))
        $ingredient_id = intval($_POST["ingredient_id"]);
    else if (isset($_POST["product_id"]))
        $product_id = intval($_POST["product_id"]);
    $quantity = intval($_POST["quantity"]);
    $cost = floatval($_POST["cost"]);
    $manager_id = 3; // CAMBIAR POR EL ID DEL MANAGER DE LA SESSION

    // Crear la reposición en REPLENISHMENTS si es un ingrediente
    if (isset($_POST["ingredient_id"])) {

        $sql_replenishment = "INSERT INTO REPLENISHMENTS (manager_id, replenishment_date, ingredient_id, quantity) VALUES (?, NOW(), ?, ?)";
        $stmt = $connection->prepare($sql_replenishment);
        $stmt->bind_param("iii", $manager_id, $ingredient_id, $quantity);
    } else if (isset($_POST["product_id"])) {

        $sql_replenishment = "INSERT INTO REPLENISHMENTS (manager_id, replenishment_date, product_id, quantity) VALUES (?, NOW(), ?, ?)";
        $stmt = $connection->prepare($sql_replenishment);
        $stmt->bind_param("iii", $manager_id, $product_id, $quantity);
    }
    $stmt->execute();
    $replenishment_id = $stmt->insert_id;

    // Insertar en TRANSACTIONS
    $sql_transaction = "INSERT INTO TRANSACTIONS (replenishment_id, transaction_money) VALUES (?, ?)";
    $stmt = $connection->prepare($sql_transaction);
    $total_cost = $quantity * $cost;
    $stmt->bind_param("id", $replenishment_id, $total_cost);
    $stmt->execute();

    // Actualizar stock en INGREDIENTS
    if (isset($_POST["ingredient_id"])) {
        $sql_update_stock = "UPDATE INGREDIENTS SET stock = stock + ? WHERE ingredient_id = ?";
        $stmt = $connection->prepare($sql_update_stock);
        $stmt->bind_param("ii", $quantity, $ingredient_id);
    } else if (isset($_POST["product_id"])) {
        $sql_update_stock = "UPDATE PRODUCTS SET stock = stock + ? WHERE product_id = ?";
        $stmt = $connection->prepare($sql_update_stock);
        $stmt->bind_param("ii", $quantity, $product_id);
    }
    $stmt->execute();
    mysqli_close($connection);

    // Redirigir al dashboard
    header("Location: ../manager.php");
    exit();
} else {
    echo "Error: No se recibió una solicitud válida.";
}
?>