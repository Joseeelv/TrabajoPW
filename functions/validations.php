<?php
require_once('.configDB.php');
function usernameExists($username) {
  // Logic to check if the username already exists
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
      die("Connection failed: " . mysqli_connect_error());
  } 
  //Prevent SQL Injection
  $stmt = $connection->prepare("SELECT COUNT(*) FROM USERS WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $count = $result->fetch_row()[0];

  $stmt->close();
  $connection->close();
  return $count > 0; //It's true if the username exists
}

function emailExists($email) {
  // Logic to check if the email already exists
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
      die("Connection failed: " . mysqli_connect_error());
  } 
  //Prevent SQL Injection
  $stmt = $connection->prepare("SELECT COUNT(*) FROM USERS WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $count = $result->fetch_row()[0];
  $stmt->close();
  $connection->close();

  return $count > 0; //It's true if the email exists
}

function validatePassword($username, $password) {
  // Conexión a la base de datos
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
      error_log("Connection failed: " . mysqli_connect_error());
      return false; // No revelamos detalles del error al usuario
  }

  try {
      // Preparamos una consulta segura para obtener el hash almacenado
      $stmt = $connection->prepare("SELECT user_secret FROM USERS WHERE username = ? LIMIT 1");
      if (!$stmt) {
          throw new Exception("Error al preparar la consulta: " . $connection->error);
      }

      // Vinculamos el parámetro y ejecutamos la consulta
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();

      // Verificamos si el usuario existe
      if ($result->num_rows === 0) {
          return false; // Usuario no encontrado (no especificamos detalles)
      }

      // Obtenemos el hash almacenado
      $row = $result->fetch_assoc();
      $storedHash = $row['user_secret'];

      // Verificamos la contraseña con password_verify
      if (password_verify($password, $storedHash)) {
          // Opcional: verificar si el hash necesita ser actualizado
          if (password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
              $newHash = password_hash($password, PASSWORD_BCRYPT);
              $updateStmt = $connection->prepare("UPDATE USERS SET user_secret = ? WHERE username = ?");
              if ($updateStmt) {
                  $updateStmt->bind_param("ss", $newHash, $username);
                  $updateStmt->execute();
                  $updateStmt->close();
              }
          }
          return true; // Contraseña válida
      } else {
          return false; // Contraseña incorrecta
      }
  } catch (Exception $e) {
      error_log("Error en validatePassword: " . $e->getMessage());
      return false;
  } finally {
      // Cerramos los recursos abiertos
      if (isset($stmt) && $stmt instanceof mysqli_stmt) {
          $stmt->close();
      }
      $connection->close();
  }
}
?>