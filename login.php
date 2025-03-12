<?php
  // Connection to the database
  $connection = new mysqli(<host>, <username>, <password>, <database>); // Replace the values with your own
  if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
  }

  // Function to register a new user
  function RegisterUser($username, $pass) {
    global $connection;
    // Hash the password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    $role = "customer"; 

    // Prevents SQL injection
    $stmt = $connection->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
  }

  // Function to verify login
  function Login($username, $pass) {
    global $connection;
    // Prevents SQL injection
    $stmt = $connection->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($pass, $user['password'])) {
      return ['id' => $user['id'], 'role' => $user['role']];
    }
    return false;
  }

  // Example of use
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
      if (RegisterUser($_POST['username'], $_POST['password'])) {
        echo "Usuario registrado con éxito como customer";
      } else {
        echo "Error al registrar el usuario";
      }
    } else if (isset($_POST['login'])) {
      $user = Login($_POST['username'], $_POST['password']);
      if ($user) {
        session_start();
        $_SESSION['id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit();
      } else {
        echo "Credenciales inválidas";
      }
    }
  }
?>
