<?php

// Establecer parámetros de cookie seguros
session_set_cookie_params([
  'lifetime' => 3600,
  'path' => '/',
  'domain' => $_SERVER['HTTP_HOST'],
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Strict'
]);

session_start(); // Iniciar la sesión

// Configuración de seguridad
const SECURITY = [
  'max_attempts' => 5,
  'lockout_time' => 1800, // 30 minutos
  'csrf_token_expire' => 3600 // 1 hora
];

// Función para generar token CSRF
function generateCsrfToken()
{
  if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
  }
  return $_SESSION['csrf_token'];
}

// Función para validar token CSRF
function validateCsrfToken($token)
{
  return hash_equals($_SESSION['csrf_token'], $token) && time() < $_SESSION['csrf_token_expire'];
}

// Función para iniciar sesión del usuario
function LoginUser($username, $pass)
{
  require_once('.configDB.php');
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
    die("Conexión fallida: " . mysqli_connect_error());
  }

  // Obtener user_secret y user_id para verificar la contraseña e iniciar sesión
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

// Función para registrar intentos de inicio de sesión fallidos (sin consultas a la base de datos)
function logFailedAttempt($username)
{
  if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = [];
  }

  if (!isset($_SESSION['failed_attempts'][$username])) {
    $_SESSION['failed_attempts'][$username] = [
      'count' => 0,
      'last_failed_attempt' => 0
    ];
  }

  $_SESSION['failed_attempts'][$username]['count']++;
  $_SESSION['failed_attempts'][$username]['last_failed_attempt'] = time();
}

// Función para verificar si el usuario está bloqueado (basado en datos de sesión)
function isUserLocked($username)
{
  if (!isset($_SESSION['failed_attempts'][$username])) {
    return false; // El usuario no está bloqueado
  }

  $failed_attempts = $_SESSION['failed_attempts'][$username]['count'];
  $last_failed_attempt = $_SESSION['failed_attempts'][$username]['last_failed_attempt'];
  $lockout_time = SECURITY['lockout_time'];
  $max_attempts = SECURITY['max_attempts'];

  if ($failed_attempts >= $max_attempts) {
    $lockout_time_remaining = (time() - $last_failed_attempt);
    if ($lockout_time_remaining < $lockout_time) {
      return true; // El usuario está bloqueado
    } else {
      // Reiniciar intentos fallidos si el tiempo de bloqueo ha pasado
      unset($_SESSION['failed_attempts'][$username]);
      return false; // El usuario no está bloqueado
    }
  }

  return false; // El usuario no está bloqueado
}

// Importar una biblioteca de validación o definir funciones de validación
require_once('validations.php');

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Validar token CSRF
  if (!validateCsrfToken($_POST['csrf_token'])) {
    die("Token CSRF inválido");
  }

  // Recopilar y sanitizar datos de entrada
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $password = $_POST['password']; // No sanitizar contraseñas

  // Inicializar un array para almacenar errores de validación
  $errors = [];

  // Verificar si el usuario está bloqueado
  if (isUserLocked($username)) {
    $errors['username'] = "La cuenta está temporalmente bloqueada. Por favor, inténtelo más tarde.";
  } else {
    // Validar nombre de usuario
    if (!usernameExists($username)) {
      $errors['username'] = "El nombre de usuario no existe.";
    }

    // Si el nombre de usuario existe, entonces validar la contraseña
    if (empty($errors)) {
      $user = LoginUser($username, $password);
      if ($user === false) {
        $errors['password'] = "Contraseña inválida.";
        logFailedAttempt($username);
      }
    }
  }

  // Verificar errores de validación
  if (empty($errors)) {
    // Inicio de sesión exitoso
    session_regenerate_id(true); // Regenerar ID de sesión
    $_SESSION['success_message'] = "Usuario conectado con éxito.";
    $_SESSION['User_ID'] = $user['user_id'];
    $_SESSION['last_activity'] = time(); // Para renovación automática de sesión
    if ($username === 'admin') {
      header("Location: admin.php"); // Redirigir a la página de administrador
    } else {
      header("Location: dashboard.php"); // Redirigir al panel de control
    }
    exit();
  } else {
    // Almacenar errores en la sesión para mostrarlos en el formulario
    $_SESSION['login_errors'] = $errors;
    header("Location: login.php"); // Redirigir de vuelta a la página de inicio de sesión
    exit();
  }
}

// Renovación automática de sesión
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
  session_regenerate_id(true);
  $_SESSION['last_activity'] = time();
}

// Forzar HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>

<body>
  <?php
  // Mostrar errores si los hay
  if (isset($_SESSION['login_errors'])) {
    foreach ($_SESSION['login_errors'] as $error) {
      echo "<p style='color: red;'>$error</p>";
    }
    unset($_SESSION['login_errors']); // Limpiar los errores después de mostrarlos
  }
  ?>
  <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <input type="text" name="username" placeholder="Nombre de usuario" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <button type="submit" name="login">Iniciar sesión</button>
  </form>
  <p>¿No tienes una cuenta? <a href="register.php">Regístrate</a></p>
</body>

</html>
