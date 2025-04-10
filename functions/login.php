<?php
// Establecer parámetros de cookie seguros
session_set_cookie_params([

  'lifetime' => 3600, // segundos (1 hora)
  'path' => '/',
  'domain' => $_SERVER['HTTP_HOST'], // Dominio actual
  'secure' => true, // Solo enviar cookies a través de HTTPS
  'httponly' => true, // No permitir acceso a JavaScript
  'samesite' => 'Strict' // Estrategia de SameSite para prevenir CSRF
]);

session_start(); // Iniciar la sesión


// Configuración de seguridad
const SECURITY = [
  'max_attempts' => 5,
  'lockout_time' => 1800, // 30 minutos
  'csrf_token_expire' => 3600 // 1 hora
];

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

    // Validamos la contraseña
    if (!validatePassword($username, $password)) {
      $errors['password'] = "Contraseña inválida.";
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
    $user = LoginUser($username, $password);
    if ($user === false) {
      $errors['password'] = "Contraseña inválida.";
      logFailedAttempt($username);
    } elseif ($user === 'inactive') {
      $errors['username'] = "Esta cuenta de manager está inactiva.";
    } else {
      session_regenerate_id(true); // Regenerar ID de sesión
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $username;
      $_SESSION['email'] = $user['email'];
      $_SESSION['user_type'] = $user['user_type'];
      $_SESSION['last_activity'] = time(); // Para renovación automática de sesión
      $_SESSION['img_src'] = $user['img_src'];

      // Obtener puntos del usuario
      $connection = include('./conexion.php');
      $stmt = $connection->prepare("SELECT points FROM CUSTOMERS WHERE user_id = ?");
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $stmt->bind_result($points);
      $stmt->fetch();
      $stmt->close();
      $_SESSION['puntos'] = $points; // Guardar puntos en la sesión

      // Eliminar variables innecesarias
      unset($_SESSION['failed_attempts']);
      unset($_SESSION['csrf_token']);
      unset($_SESSION['csrf_token_expire']);

      // Redirección basada en el user_type
      switch ($_SESSION['user_type']) {
        case 'admin':
          header("Location: ./admin.php");
          break;
        case 'manager':
          header("Location: ./manager_index.php");
          break;
        case 'customer':
          header("Location: ./dashboard.php");
          break;
        default:
          header("Location: ./login.php");
          break;
      }
      exit();
    }
  } else {
    // Almacenar errores en la sesión para mostrarlos en el formulario
    $_SESSION['login_errors'] = $errors;
    header("Location: ./login.php"); // Redirigir de vuelta a la página de inicio de sesión
    exit();
  }
}

// Renovación automática de sesión
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {

  session_unset();
  session_destroy();
  session_regenerate_id(true);
  $_SESSION['last_activity'] = time();
  header("Location: ../index.php");
  exit();

}

// Forzar HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

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
  $connection = include('./conexion.php');
  // Obtener user_secret, user_id, y verificar si es un manager activo
  $stmt = $connection->prepare("
    SELECT u.user_id, u.user_secret, u.user_type, u.email, u.img_src, m.employee 
    FROM USERS u
    LEFT JOIN MANAGERS m ON u.user_id = m.user_id
    WHERE u.username = ?
  ");
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

  // Verificar contraseña y si es un manager activo
  if (password_verify($pass, $user['user_secret'])) {
    if ($user['user_type'] === 'manager' && $user['employee'] != 1) {
      return 'inactive';
    }
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

// Función para verificar si el usuario está bloqueado (basado en datos de sesión).

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
?>

<html>

<head>
  <meta charset="UTF-8">
  <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/register.css">
</head>

<body>
  <?php include('./navbar.php'); ?>
  <main>
    <h1>Inicia Sesión</h1>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <input type="text" name="username" placeholder="Nombre de usuario" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <button type="submit" name="login">Iniciar sesión</button>

      <?php
      // Mostrar errores si los hay
      if (isset($_SESSION['login_errors'])) {
        echo "<div class='error-container'>";
        foreach ($_SESSION['login_errors'] as $field => $error) {
          echo "<p class='error'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>";
        }
        echo "</div>";
        unset($_SESSION['login_errors']); // Limpia los errores después de mostrarlos
      }
      ?>

    </form>
    <p>¿No tienes una cuenta? <a href="register.php">Regístrate</a></p>
  </main>
  <?php include('./footer.php'); ?>
</body>

</html>