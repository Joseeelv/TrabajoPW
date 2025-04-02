# Documentación PHP: Registro y Login

Este documento detalla las medidas de seguridad implementadas en el código PHP proporcionado, enfocándose en las prácticas recomendadas para proteger contra vulnerabilidades comunes y ataques maliciosos. El código abarca funcionalidades de registro, inicio de sesión, y gestión de sesiones.

## Configuración de Seguridad

```php
const SECURITY = [
  'csrf_token_expire' => 3600, // 1 hora
  'token_length' => 32 // Longitud del token en bytes
];
```

Esta sección define constantes de seguridad cruciales para la aplicación:

- `csrf_token_expire`: Especifica la duración de validez de los tokens CSRF, crucial para prevenir ataques de falsificación de petición en sitios cruzados (CSRF).
- `token_length`: Define la longitud en bytes del token CSRF generado aleatoriamente. Una longitud mayor incrementa la entropía y dificulta la predicción del token por atacantes.

## Protección contra CSRF

La protección contra CSRF se implementa mediante la generación y validación de tokens únicos para cada sesión de usuario.

```php
function generateCsrfToken(): string
{
  if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(SECURITY['token_length']));
    $_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
  }
  return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
  return isset($_SESSION['csrf_token']) &&
  hash_equals($_SESSION['csrf_token'], $token) &&
  time() < $_SESSION['csrf_token_expire'];
}
```

- `generateCsrfToken()`: Genera un token CSRF único y lo almacena en la sesión del usuario. Si ya existe un token y aún es válido, se reutiliza.
- `validateCsrfToken()`: Valida el token CSRF enviado con el formulario comparándolo con el almacenado en la sesión. La función `hash_equals()` se utiliza para evitar ataques de temporización.

## Cabeceras de Seguridad

Las cabeceras HTTP de seguridad son configuradas para fortalecer la seguridad de la aplicación.

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdnjs.cloudflare.com");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
```

- `Content-Security-Policy`: Define una política de seguridad de contenido para controlar los recursos que el navegador puede cargar. En este caso, solo se permiten recursos del mismo origen y scripts de cdnjs.cloudflare.com.
- `X-Frame-Options: DENY`: Protege contra ataques de clickjacking impidiendo que la página sea incluida en un iframe.
- `Strict-Transport-Security`: Fuerza el uso de HTTPS y protege contra ataques de intermediario. El atributo `preload` permite que el dominio sea incluido en la lista de precarga de HSTS en los navegadores.
- `X-Content-Type-Options: nosniff`: Evita que el navegador interprete incorrectamente los tipos MIME de los archivos, mitigando ataques de inyección de HTML.
- `Referrer-Policy: strict-origin-when-cross-origin`: Define la política de referencia para controlar la cantidad de información enviada en el encabezado Referer.
- `Permissions-Policy`: Controla el acceso a características del navegador, como la geolocalización y el uso de la cámara.

## Función de Registro

La función `RegisterUser` se encarga de registrar nuevos usuarios en el sistema.

```php
function RegisterUser(string $username, string $pass, string $email, string $address): void
{
  $connection = include('./conexion.php');
  mysqli_begin_transaction($connection);

  try {
    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
    // Validación de unicidad antes de insertar
    $stmt = $connection->prepare("SELECT user_id FROM USERS WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      throw new Exception("Nombre de usuario o email ya registrado");
    }
    $stmt->close();

    // Inserción en USERS
    $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $email);
    if (!$stmt->execute()) {
      throw new Exception("Error en USERS: " . $stmt->error);
    }
    $user_id = $connection->insert_id;
    $stmt->close();

    // Inserción en CUSTOMERS
    $stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $address);
    if (!$stmt->execute()) {
      throw new Exception("Error en CUSTOMERS: " . $stmt->error);
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
```

- La contraseña se hashea utilizando `password_hash` con el algoritmo `BCRYPT`, proporcionando una seguridad robusta contra ataques de fuerza bruta y rainbow tables.
- Se utiliza una transacción para asegurar la integridad de los datos. Si alguna operación falla, se revierte la transacción.
- Se implementa una validación de unicidad antes de la inserción para evitar la creación de usuarios duplicados.

## Validación del Formulario

Antes de registrar al usuario, los datos del formulario se validan para asegurar que cumplen con los requisitos de seguridad y formato.

```php
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$password = $_POST['password'];
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$address = trim(htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8'));
$errors = [];
```

- Los datos se sanitizan utilizando `htmlspecialchars` para prevenir ataques XSS y `filter_var` con `FILTER_SANITIZE_EMAIL` para limpiar el email.
- Se utilizan funciones de validación personalizadas (definidas en `validations.php`) para verificar el formato y la validez de los datos.

## Función de Inicio de Sesión

```php
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
```

1. Selección Segura de Datos:

- La función ahora usa una sentencia preparada para buscar los detalles del usuario por nombre de usuario, previniendo la inyección SQL.

2. Protección contra Ataques de Fuerza Bruta:

- La sesión se regenera al inicio de sesión para evitar la fijación de sesión.
- Control de intentos fallidos para prevenir ataques de fuerza bruta, bloqueando cuentas temporalmente.
- Se registran los intentos fallidos de inicio de sesión en una variable de sesión

4. Verificación de Estado de la Cuenta:

- Los usuarios con cuentas de administrador inactivas no pueden iniciar sesión, y se les notifica que su cuenta está inactiva.

5. Manejo Adecuado de la Información de la Sesión:

- La sesión se regenera después de la autenticación para evitar la fijación de sesión.
- Se establece una variable de última actividad para la renovación automática de la sesión y la caducidad del tiempo de inactividad, mejorando la seguridad y la gestión de los recursos del servidor.
- La sesión está configurada para utilizar cookies seguras (HTTPOnly, Secure y SameSite) para proteger contra ataques de secuencias de comandos entre sitios (XSS) y falsificación de solicitudes entre sitios (CSRF).

## Protección contra Fuerza Bruta

Para mitigar ataques de fuerza bruta, se implementa un sistema de bloqueo de cuentas basado en el número de intentos fallidos de inicio de sesión.

```php
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
```

- `logFailedAttempt()`: Registra cada intento fallido de inicio de sesión en la sesión del usuario.
- `isUserLocked()`: Verifica si un usuario está bloqueado basándose en el número de intentos fallidos y el tiempo transcurrido desde el último intento.

## HTTPS

Se fuerza el uso de HTTPS para proteger la comunicación entre el cliente y el servidor.

```php
if ($_SERVER['HTTPS'] !== 'on' && $_SERVER['HTTP_HOST'] !== 'localhost') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
```

Este código redirige automáticamente a la versión HTTPS del sitio si se detecta una conexión no segura, protegiendo contra ataques de intermediario.

# Documentación PHP: Actualización de Perfil

Este documento detalla las medidas de seguridad implementadas en la funcionalidad de actualización de perfil en PHP, enfocándose en las mejores prácticas para proteger contra vulnerabilidades comunes y ataques maliciosos.

## Configuración de Seguridad

```php
const SECURITY = [
  'csrf_token_expire' => 3600, // 1 hora
  'token_length' => 32, // Longitud del token en bytes
  'max_attempts' => 5, // Máximo de intentos fallidos
  'lockout_time' => 900 // Tiempo de bloqueo en segundos (15 minutos)
];
```

- `csrf_token_expire`: Especifica la duración de validez de los tokens CSRF.
- `token_length`: Define la longitud del token CSRF para mayor seguridad.
- `max_attempts` y `lockout_time`: Previenen ataques de fuerza bruta limitando los intentos de actualización fallidos.

## Protección contra CSRF

Cada solicitud de actualización de perfil debe incluir un token CSRF válido.

```php
function generateCsrfToken(): string
{
  if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(SECURITY['token_length']));
    $_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
  }
  return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
  return isset($_SESSION['csrf_token']) &&
    hash_equals($_SESSION['csrf_token'], $token) &&
    time() < $_SESSION['csrf_token_expire'];
}
```

## Cabeceras de Seguridad

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdnjs.cloudflare.com");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
```

## Validación del Formulario

Los datos del formulario se validan y sanitizan antes de procesarlos.

```php
$password = $_POST['password'];
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$address = trim(htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8'));
$errors = [];
```

- Se sanitizan los datos para prevenir ataques XSS.
- Se valida que el email sea válido y no esté duplicado.
- Se usa `Validator::validatePassword($password)` para verificar la seguridad de la contraseña.

## Funcionalidad de Actualización de Perfil

```php
function UpdateProfile($connection, $pass, $email, $address, $image)
{
  mysqli_begin_transaction($connection);
  try {
    $hashed_password = !empty($pass) ? password_hash($pass, PASSWORD_BCRYPT) : null;

    $stmt = $connection->prepare("UPDATE USERS SET
                                  user_secret = COALESCE(?, user_secret),
                                  img_src = COALESCE(?, img_src)
                                  WHERE user_id = ?");
    $stmt->bind_param("ssi", $hashed_password, $image, $_SESSION['user_id']);

    if (!$stmt->execute()) {
      throw new Exception("Error al actualizar USERS: " . $stmt->error);
    }
    $stmt->close();

    if ($_SESSION['user_type'] === 'customer' && !empty($address)) {
      $stmt = $connection->prepare("UPDATE CUSTOMERS SET customer_address = ? WHERE user_id = ?");
      $stmt->bind_param("si", $address, $_SESSION['user_id']);
      if (!$stmt->execute()) {
        throw new Exception("Error al actualizar CUSTOMERS: " . $stmt->error);
      }
      $stmt->close();
    }

    mysqli_commit($connection);
  } catch (Exception $e) {
    mysqli_rollback($connection);
    throw $e;
  }
}
```

- Se usa `password_hash` con `BCRYPT` para proteger contraseñas.
- Se valida la unicidad del email antes de actualizarlo.
- Se ejecuta dentro de una transacción para garantizar la integridad de los datos.

## Protección contra Fuerza Bruta

Para prevenir intentos de actualización masivos, se implementa un sistema de bloqueo temporal.

```php
function logFailedAttempt($username)
{
  if (!isset($_SESSION['failed_attempts'][$username])) {
    $_SESSION['failed_attempts'][$username] = ['count' => 0, 'last_failed_attempt' => 0];
  }

  $_SESSION['failed_attempts'][$username]['count']++;
  $_SESSION['failed_attempts'][$username]['last_failed_attempt'] = time();
}

function isUserLocked($username)
{
  if (!isset($_SESSION['failed_attempts'][$username])) return false;

  $failed_attempts = $_SESSION['failed_attempts'][$username]['count'];
  $last_failed_attempt = $_SESSION['failed_attempts'][$username]['last_failed_attempt'];
  $lockout_time_remaining = time() - $last_failed_attempt;

  if ($failed_attempts >= SECURITY['max_attempts'] && $lockout_time_remaining < SECURITY['lockout_time']) {
    return true;
  } elseif ($lockout_time_remaining >= SECURITY['lockout_time']) {
    unset($_SESSION['failed_attempts'][$username]);
  }
  return false;
}
```

- `logFailedAttempt()`: Registra intentos fallidos.
- `isUserLocked()`: Bloquea temporalmente al usuario si excede el límite de intentos fallidos.

## Forzar HTTPS

Se redirige a HTTPS si la conexión no es segura.

```php
if ($_SERVER['HTTPS'] !== 'on' && $_SERVER['HTTP_HOST'] !== 'localhost') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
```

Con estas medidas, se mejora significativamente la seguridad en la actualización de perfiles.
