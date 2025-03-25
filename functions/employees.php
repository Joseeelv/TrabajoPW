<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Empleados</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/manager.css">
</head>

<body>
  <?php include('./navbar.php'); ?>
  <main>
    <h1>Empleados de Kebab Society</h1>

    <?php
    // Mostrar mensajes de éxito o error
    if (isset($_SESSION['success_message'])) {
      echo "<p class='success'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
      unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
      echo "<p class='error'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
      unset($_SESSION['error_message']);
    }

    $connection = include('./conexion.php');

    try {
      // Obtener managers 
      $stmt = $connection->prepare("SELECT USERS.*, MANAGERS.* FROM USERS INNER JOIN MANAGERS ON USERS.user_id = MANAGERS.user_id WHERE USERS.user_type = 'manager'");
      $stmt->execute();
      $managers = $stmt->get_result();

      if ($managers->num_rows > 0) {
        echo '<form method="POST" action="./despedir.php">';
        echo '<table>';
        echo '<thead><tr><th>Nombre</th><th>Email</th><th>Salario</th><th>Despedir</th></tr></thead>';
        echo '<tbody>';
        while ($row = $managers->fetch_assoc()) {
          if ($row['employee'] == 1) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['salary']) . '€</td>';
            echo '<td><input type="checkbox" name="despedir[]" value="' . htmlspecialchars($row['user_id']) . '"></td>';
            echo '</tr>';
          }
        }
        echo '</tbody>';
        echo '</table>';
        echo '<button type="submit">Despedir seleccionados</button>';
        echo '</form>';
      } else {
        echo '<p>No se encontraron empleados.</p>';
      }
    } catch (Exception $e) {
      echo '<p class="error">Error al obtener los empleados: ' . htmlspecialchars($e->getMessage()) . '</p>';
    } finally {
      $stmt->close();
      $connection->close();
    }
    ?>
  </main>
  <?php include('./footer.php'); ?>
</body>

</html>