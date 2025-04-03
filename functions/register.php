<?php
session_start(); // Inicia la sesión
const SECURITY = [
    'csrf_token_expire' => 3600, // 1 hora
    'rate_limit' => 5, // Máximo de intentos por hora
    'password_min_strength' => 3 // Nivel de seguridad de la contraseña (0-4)
];

// Habilitar visualización de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ************** PROTECCIÓN CSRF ************** //
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token)
{
    return hash_equals($_SESSION['csrf_token'], $token) && time() < $_SESSION['csrf_token_expire'];
}

// Cabeceras de seguridad
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=63072000; includeSubDomains");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=()");

// Función para registrar un nuevo usuario
function RegisterUser($username, $pass, $email, $address)
{
    $connection = include('./conexion.php');

    mysqli_begin_transaction($connection);

    $address = empty($address) ? "" : $address; // Asegurar que no sea NULL

    try {
        // Hashea la contraseña
        $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

        // Inserta en la tabla USERS
        $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar en la tabla USERS: " . $stmt->error);
        }
        $user_id = $connection->insert_id;
        $stmt->close();

        // Inserta en la tabla CUSTOMERS
        $stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $address);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar en la tabla CUSTOMERS: " . $stmt->error);
        }
        $stmt->close();

        mysqli_commit($connection);
    } catch (Exception $e) {
        mysqli_rollback($connection);
        throw $e;
    } finally {
        $connection->close();
    }
}

// Importa la clase de validación
require_once('validations.php');

// Comprueba si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recolecta y sanitiza los datos de entrada
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
    $password = $_POST['password'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = trim(htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8'));

    // Array para almacenar errores
    $errors = [];

    // Validación del usuario (obligatorio)
    $username_errors = Validator::validateUsername($username);
    if (!empty($username_errors)) {
        $errors['username'] = implode(" ", $username_errors);
    }

    // Validación de la contraseña (obligatoria)
    $password_errors = Validator::validatePassword($password);
    if (!empty($password_errors)) {
        $errors['password'] = implode(" ", $password_errors);
    }

    // Validación del email (obligatorio)
    $email_errors = Validator::validateEmail($email);
    if (!empty($email_errors)) {
        $errors['email'] = implode(" ", $email_errors);
    }

    // Validación de la dirección (obligatoria)
    if (empty($address)) {
        $errors['address'] = "La dirección es obligatoria.";
    }

    // Validación CSRF
    if (!validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception("Token CSRF inválido");
    }

    // Si no hay errores, registrar el usuario
    if (empty($errors)) {
        try {
            RegisterUser($username, $password, $email, $address);
            $_SESSION['success_message'] = "Usuario registrado con éxito.";
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            error_log("Error de registro: " . $e->getMessage());
            $errors['registration'] = "Error de registro: " . $e->getMessage();
        }
    }

    // Almacena los errores en la sesión para mostrarlos en el formulario
    $_SESSION['register_errors'] = $errors;
}

// Fuerza HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>

<body class="body-login">
    <?php include('./navbar.php'); ?>
    <main>
        <h1>Regístrate</h1>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="text" name="username" placeholder="Nombre de usuario" required
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <div>
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <div class="password-strength-meter">
                    <div class="password-strength-meter-fill"></div>
                </div>
                <ul class="password-checklist">
                    <li id="length">Al menos 8 caracteres</li>
                    <li id="uppercase">Una letra mayúscula</li>
                    <li id="lowercase">Una letra minúscula</li>
                    <li id="number">Un número</li>
                    <li id="special">Un carácter especial</li>
                </ul>
            </div>
            <input type="text" name="email" placeholder="Email" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="text" name="address" placeholder="Dirección" required
                value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
            <?php
            if (isset($_SESSION['success_message'])) {
                echo "<p class='success'>" . $_SESSION['success_message'] . "</p>";
                unset($_SESSION['success_message']);
            }
            if (!empty($_SESSION['register_errors'])) {
                echo "<div class='error-container'>";
                foreach ($_SESSION['register_errors'] as $key => $error) {
                    if (is_array($error)) {
                        foreach ($error as $msg) {
                            echo "<p class='error'>" . htmlspecialchars($msg) . "</p>";
                        }
                    } else {
                        echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
                    }
                }
                echo "</div>";
                unset($_SESSION['register_errors']);
            }
            ?>
            <button type="submit" name="register">Registrarse</button>
        </form>

        <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
        <script src="../assets/js/password-strength-meter.js"></script>
    </main>
    <?php include('./footer.php'); ?>
</body>

</html>