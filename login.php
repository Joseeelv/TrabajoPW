<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
</head>
<body>
  <h1>Login</h1>
  <form method="POST" action="login.php">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Log in</button>
  </form>
</body>
</html>

<?php
// Function to login
function LoginUser($username, $pass) {
    require_once('.configDB.php');
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Obtener salt y hash almacenado
    $stmt = $connection->prepare("SELECT user_id, user_secret FROM USERS WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $connection->close();
        return false;
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    $connection->close();

    // Verificar contraseña
    if (password_verify($pass, $user['user_secret'])) {
        return $user;
    } else {
        return false;
    }
}

// Example of usage
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $user = LoginUser($_POST['username'], $_POST['password']);
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            header('Location: dashboard.php');
            exit();
        } else {
            echo "Credenciales inválidas";
        }
    }
}
?>
