<?php
session_start();
$connection = include('./conexion.php');

if (!$connection) {
  die("Database connection failed.");
}

try {
  // Inicia una transacción para asegurar consistencia
  $connection->begin_transaction();

  // Preparar la consulta para actualizar la tabla MANAGERS
  $stmt = $connection->prepare("UPDATE MANAGERS SET employee = 0 WHERE user_id = ?");
  if (!$stmt) {
      throw new Exception("Error preparando la consulta: " . $connection->error);
  }

  // Verificar si se enviaron datos desde el formulario
  if (isset($_POST['despedir']) && is_array($_POST['despedir'])) {
      foreach ($_POST['despedir'] as $user_id) {
          // Vincular parámetros y ejecutar la consulta
          $stmt->bind_param("i", $user_id);
          if (!$stmt->execute()) {
              throw new Exception("Error ejecutando la consulta: " . $stmt->error);
          }
      }
      $_SESSION['success_message'] = "Los empleados seleccionados han sido despedidos correctamente.";
  } else {
      $_SESSION['error_message'] = "No se seleccionó ningún empleado para despedir.";
  }

  // Confirmar transacción
  $connection->commit();
} catch (Exception $e) {
  // Revertir transacción en caso de error
  $connection->rollback();
  $_SESSION['error_message'] = "Error al despedir empleados: " . $e->getMessage();
} finally {
  // Cerrar statement y conexión
  if (isset($stmt)) {
      $stmt->close();
  }
  $connection->close();
}

// Redirigir al listado de empleados con mensajes de éxito o error
header("Location: ./employees.php");
exit();
?>