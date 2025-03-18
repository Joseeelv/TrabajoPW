<?php
require_once('.configDB.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ingredient_id"]) && isset($_POST["quantity"])) {
    $ingredient_id = intval($_POST["ingredient_id"]);
    $quantity = intval($_POST["quantity"]);
    $manager_id = 3; // CAMBIAR POR EL ID DEL MANAGER DE LA SESSION

    // Crear la reposición en REPLENISHMENTS
    $sql_replenishment = "INSERT INTO REPLENISHMENTS (manager_id, replenishment_date) VALUES (?, NOW())";
    $stmt = $connection->prepare($sql_replenishment);
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $replenishment_id = $stmt->insert_id;

    // Insertar en REPLENISHMENTS_DETAILS
    $sql_details = "INSERT INTO REPLENISHMENTS_DETAILS (replenishment_id, ingredient_id, quantity) VALUES (?, ?, ?)";
    $stmt = $connection->prepare($sql_details);
    $stmt->bind_param("iii", $replenishment_id, $ingredient_id, $quantity);
    $stmt->execute();

    // Insertar en TRANSACTIONS
    $sql_transaction = "INSERT INTO TRANSACTIONS (order_id, replenishment_id, transaction_money) 
                        VALUES (NULL, ?, (SELECT cost * ? FROM INGREDIENTS WHERE ingredient_id = ?))";
    $stmt = $connection->prepare($sql_transaction);
    $stmt->bind_param("iii", $replenishment_id, $quantity, $ingredient_id);
    $stmt->execute();
    
    // Actualizar stock en INGREDIENTS
    $sql_update_stock = "UPDATE INGREDIENTS SET stock = stock + ? WHERE ingredient_id = ?";
    $stmt = $connection->prepare($sql_update_stock);
    $stmt->bind_param("ii", $quantity, $ingredient_id);
    $stmt->execute();


    mysqli_close($connection);

    // Redirigir al dashboard
    header("Location: ../manager.php");
    exit();
} else {
    echo "Error: No se recibió una solicitud válida.";
}
?>