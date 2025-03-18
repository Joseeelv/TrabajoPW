# Sistema de Registro de Usuarios

Esta documentación describe un sistema de registro de usuarios basado en PHP. El sistema incluye características como protección CSRF, validación de entrada, hash de contraseñas e interacción con la base de datos.

## Componentes Principales

El sistema se compone de:

- Configuración de seguridad
- Protección CSRF
- Función de registro de usuarios
- Validación y saneamiento de la entrada
- Manejo de errores
- Cabeceras de seguridad

## Configuración de Seguridad

El sistema utiliza una constante `SECURITY` para definir parámetros clave de seguridad:

```php
const SECURITY = [
	'csrf_token_expire' => 3600, // 1 hora
	'rate_limit' => 5, // Intentos máximos por hora
	'password_min_strength' => 3 // Nivel de seguridad de contraseña (0-4)
];
```

## Protección CSRF

La protección CSRF (Cross-Site Request Forgery) se implementa utilizando verificación basada en tokens:

### Funciones:

- `generateCsrfToken()`: Genera un nuevo token CSRF o devuelve uno existente si aún es válido.
- `validateCsrfToken($token)`: Valida el token CSRF proporcionado contra el almacenado.

## Función de Registro de Usuario

La función `RegisterUser($username, $pass, $email)` maneja el proceso de registro de un nuevo usuario:

```php
function RegisterUser($username, $pass, $email) {
  require_once('.configDB.php');
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
      die("Connection failed: " . mysqli_connect_error());
  }

  // Hash the password
  $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

  // Insert into USERS table
  $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $hashed_password, $email);
  if (!$stmt->execute()) {
      throw new Exception("Error inserting into USERS table: " . $stmt->error);
  }
  $user_id = $connection->insert_id;
  $stmt->close();

  // Insert into CUSTOMERS table
  $stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
  $address = "No address";
  $stmt->bind_param("is", $user_id, $address);
  if (!$stmt->execute()) {
      throw new Exception("Error inserting into CUSTOMERS table: " . $stmt->error);
  }
  $stmt->close();
  $connection->close();
}
```

### Propósito

El propósito principal de esta función es registrar un nuevo usuario en la base de datos insertando su información en las tablas `USERS` y `CUSTOMERS`.

## Validación de Entrada

El sistema realiza una validación exhaustiva de la entrada:

### Validación del Nombre de Usuario

- Obligatorio.
- Longitud entre 3 y 20 caracteres.
- Solo permite letras, números y guiones bajos.
- Comprueba la unicidad en la base de datos.

```php
// Validate username
  if (empty($username)) {
      $errors['username'] = "Username is required.";
  } elseif (strlen($username) < 3 || strlen($username) > 20) {
      $errors['username'] = "Username must be between 3 and 20 characters long.";
  } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
      $errors['username'] = "Username can only contain letters, numbers, and underscores.";
  } elseif (usernameExists($username)) { // Very if the username already exists
      $errors['username'] = "This username is already taken.";
}
```

### Validación del Correo Electrónico

- Obligatorio.
- Debe ser un formato de correo electrónico válido.
- Comprueba la unicidad en la base de datos.

```php
// Validate email
  if (empty($email)) {
      $errors['email'] = "Email is required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = "Please enter a valid email address.";
  } elseif (emailExists($email)) { // Verify if the email already exists
      $errors['email'] = "This email is already registered.";
  }
```

### Validación de la Contraseña

- Obligatorio.
- Longitud mínima de 8 caracteres.
- Debe contener mayúsculas, minúsculas, números y caracteres especiales.

```php
// Validate password
	if (empty($password)) {
		$errors['password'] = "Password is required.";
	} elseif (strlen($password) < 8) {
		$errors['password'] = "The password must be at least 8 characters long.";
	} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,}$/', $password)) {
		$errors['password'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
}
```

## Flujo de Ejecución Principal

1. Inicia una sesión PHP
2. Comprueba si el formulario fue enviado vía POST
3. Sanitiza y valida los datos de entrada
4. Valida el token CSRF
5. Implementa limitación de intentos
6. Si la validación pasa, intenta registrar al usuario
7. Maneja escenarios de éxito o fracaso

## Cabeceras de Seguridad

El sistema establece varias cabeceras de seguridad para mejorar la protección:

```php
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=63072000; includeSubDomains");
header("X-Content-Type-Options: nosniff");
```

## Manejo de Errores

- Los errores de validación se almacenan en el array `$errors`.
- Si el registro falla, se registra un error y se muestra un mensaje amigable al usuario.
- Los errores se almacenan en la sesión para ser mostrados en el formulario.

```php
// Initialize an array to store validation errors
$errors = [];
  // Check for validation errors
  if (empty($errors)) {
    try {
      RegisterUser($username, $password, $email);
      // Successful registration
      $_SESSION['success_message'] = "User registered successfully.";
      header("Location: login.php");
      exit();
    } catch (Exception $e) {
      error_log("Registration error: " . $e->getMessage());
      $errors['registration'] = "An error occurred during registration. Please try again later.";
    }
  }

// Store errors in session to display them on the form
$_SESSION['register_errors'] = $errors;
```

## Uso del Sistema

1. El usuario envía el formulario de registro.
2. El sistema valida y sanea la entrada.
3. Si la validación tiene éxito, se llama a `RegisterUser`.
4. En caso de registro exitoso, el usuario es redirigido a la página de inicio de sesión.
5. Si el registro falla, se muestra un mensaje de error.

## Medidas de seguridad

En esta función de registro, se han implementado varias medidas de seguridad para proteger los datos y prevenir ataques comunes:

### Prevención de Inyección SQL

Se utilizan sentencias preparadas (prepared statements) para todas las consultas SQL. Esto separa los datos de la estructura de la consulta, evitando así la inyección SQL.

```php
$stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $username, $hashed_password, $email);
	if (!$stmt->execute()) {
		throw new Exception("Error inserting into USERS table: " . $stmt->error);
	}
	$user_id = $connection->insert_id;
	$stmt->close();
```

### Hasheo de contraseñas

Se almacenan las contraseñas hasheadas para una mayor seguridad y prevenir ataques de robo de credenciales.

```php
$hashed_password = password_hash($pass, PASSWORD_BCRYPT);
```

### Prevención contra XSS

Se hace uso de funciones específicas para el saneamiento de algunos campos con el fin de prevenir ataques **XSS persistente**, esto se hace mediante la validación con `htmlspecialchars`:

```php
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$email = trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'));
```

### Prevención de ataques CSRF

Se implementa un sistema de tokens CSRF para proteger contra ataques de falsificación de solicitudes entre sitios:

```php
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

// Uso en el flujo principal
if (!validateCsrfToken($_POST['csrf_token'])) {
throw new Exception("Invalid CSRF token");
}
```

### Limitación del número de intentos

Se implementa una limitación de intentos para prevenir ataques de fuerza bruta:

```php
$_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
if ($_SESSION['attempts'] > 5) {
  die("Demasiados intentos. Intenta más tarde.");
}
```

### Forzar HTTPS

Se fuerza el uso de HTTPS para proteger la información transmitida:

```php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
```

# Sistema de Inicio de Sesión de Usuarios

Este documento describe un sistema de inicio de sesión de usuarios basado en PHP. El sistema incluye características como protección CSRF, limitación de intentos, validación de entrada, hash de contraseñas y manejo de sesiones seguras.

## Componentes Principales

El sistema se compone de:

- Configuración de seguridad.
- Protección CSRF
- Funciones auxiliares (registro de intentos, bloqueo).
- Función de inicio de sesión.
- Validación y saneamiento de la entrada.
- Manejo de errores.
- Medidas de seguridad.

## Configuración de Seguridad

El sistema utiliza una constante `SECURITY` para definir parámetros clave de seguridad:

```php
// Security configuration
const SECURITY = [
  'max_attempts' => 5,
  'lockout_time' => 1800, // 30 minutes
  'csrf_token_expire' => 3600 // 1 hour
];
```

## Protección CSRF

La función `generateCsrfToken()` genera un token CSRF para proteger contra ataques de falsificación de solicitudes entre sitios. La función `validateCsrfToken($token)` valida el token CSRF proporcionado contra el almacenado:

```php
function generateCsrfToken()
{
  if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
  }
  return $_SESSION['csrf_token'];
}

// Function to validate CSRF token
function validateCsrfToken($token)
{
  return hash_equals($_SESSION['csrf_token'], $token) && time() < $_SESSION['csrf_token_expire'];
}

```

## Registrar Intento Fallido

La función `logFailedAttempt($username)` registra los intentos fallidos de inicio de sesión en la sesión:

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
```

## Verificar Usuario Bloqueado

La función `isUserLocked($username)` verifica si un usuario está bloqueado basándose en los datos de la sesión:

```php
function isUserLocked($username)
{
  if (!isset($_SESSION['failed_attempts'][$username])) {
    return false; // User is not locked
  }

  $failed_attempts = $_SESSION['failed_attempts'][$username]['count'];
  $last_failed_attempt = $_SESSION['failed_attempts'][$username]['last_failed_attempt'];
  $lockout_time = SECURITY['lockout_time'];
  $max_attempts = SECURITY['max_attempts'];

  if ($failed_attempts >= $max_attempts) {
    $lockout_time_remaining = (time() - $last_failed_attempt);
    if ($lockout_time_remaining < $lockout_time) {
      return true; // User is locked
    } else {
      // Reset failed attempts if lockout time has passed
      unset($_SESSION['failed_attempts'][$username]);
      return false; // User is not locked
    }
  }

  return false; // User is not locked
}
```

## Función de Inicio de Sesión

La función `LoginUser($username, $pass)` maneja el proceso de autenticación de un usuario:

```php
function LoginUser($username, $pass)
{
  require_once('.configDB.php');
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
  }

  // Get user_secret and user_id in order to verify password & login
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

  // Verify password
  if (password_verify($pass, $user['user_secret'])) {
    return $user;
  } else {
    return false;
  }
}
```

## Validación de Entrada

El sistema realiza las siguientes validaciones:

```php
// If username exists, then validate password
if (empty($errors)) {
  $user = LoginUser($username, $password);
  if ($user === false) {
    $errors['password'] = "Invalid password.";
    logFailedAttempt($username);
  }
}
```

## Flujo de Ejecución Principal

1. Inicia una sesión PHP con parámetros de cookie seguros.
2. Valida el token CSRF.
3. Recolecta y sanitiza los datos de entrada.
4. Verifica si el usuario está bloqueado.
5. Valida el nombre de usuario y la contraseña.
6. Si la validación es exitosa, regenera el ID de sesión e inicia la sesión del usuario.
7. Si hay errores, los almacena en la sesión y redirige al usuario al formulario de inicio de sesión.

## Manejo de Errores

- Los errores de validación se almacenan en el array `$errors`.
- Si hay errores, se almacenan en la sesión y se redirige al usuario de vuelta al formulario de inicio de sesión.

```php
// Initialize an array to store validation errors
$errors = [];

// Check for validation errors
if (empty($errors)) {
  // Successful login
  session_regenerate_id(true); // Regenerate session ID
  $_SESSION['success_message'] = "User logged in successfully.";
  $_SESSION['User_ID'] = $user['user_id'];
  $_SESSION['last_activity'] = time(); // For automatic session renewal
  header("Location: dashboard.php"); // Redirect to the dashboard
  exit();
} else {
  // Store errors in session to display them on the form
  $_SESSION['login_errors'] = $errors;
  header("Location: login.php"); // Redirect back to the login page
  exit();
}
```

## Medidas de Seguridad

### Parámetros de Cookie Seguros

Se configuran parámetros de cookie seguros para la sesión:

```php
// Set secure cookie parameters
session_set_cookie_params([
  'lifetime' => 3600,
  'path' => '/',
  'domain' => $_SERVER['HTTP_HOST'],
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Strict'
]);
```

### Prevención de Inyección SQL

Se utilizan sentencias preparadas para todas las consultas SQL:

```php
$stmt = $connection->prepare("SELECT user_id, user_secret FROM USERS WHERE username = ?");
$stmt->bind_param("s", $username);
```

### Protección de Contraseñas

Las contraseñas se verifican de forma segura utilizando la función `password_verify()`:

```php
if (password_verify($pass, $user['user_secret']))
```

### Sanitización de Entrada

El nombre de usuario se sanitiza para prevenir ataques XSS:

```php
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
```

### Prevención de Ataques CSRF

Se implementa un sistema de tokens CSRF para proteger contra ataques de falsificación de solicitudes entre sitios.

### Limitación de Intentos

Se implementa una limitación de intentos de inicio de sesión para prevenir ataques de fuerza bruta.

## Uso del Sistema

1. El usuario envía el formulario de inicio de sesión.
2. El sistema valida el token CSRF.
3. El sistema valida y sanitiza la entrada.
4. Si la validación es exitosa, se autentica al usuario.
5. En caso de inicio de sesión exitoso, el usuario es redirigido al dashboard.
6. Si hay errores, se redirige al usuario de vuelta al formulario de inicio de sesión con mensajes de error.

## Renovación Automática de Sesión

Se implementa una renovación automática de sesión para mejorar la seguridad:

```php
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
  session_regenerate_id(true);
  $_SESSION['last_activity'] = time();
}
```

## Forzar HTTPS

Se fuerza el uso de HTTPS para proteger la información transmitida:

```php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
```

# Implementación de funciones de validación

Encontramos tres funciones esenciales para la validación de credenciales en un sistema de autenticación:

1. `usernameExists()`: Verifica la existencia de un nombre de usuario.
2. `emailExists()`: Comprueba si un email está registrado.
3. `validatePassword()`: Valida una contraseña (implementación actual con advertencia).

## Función `usernameExists()`

### Propósito

La función `usernameExists` se utiliza para comprobar de forma segura si el nombre de usuario ya está registrado en la base de datos. Está diseñada para garantizar la seguridad y prevenir ataques comunes como inyección SQL.

### Implementación

```php
function usernameExists($username) {
  // Conexión y lógica de verificación...
}
```

#### Parámetros

- `$username`: Nombre de usuario a verificar (string)

#### Proceso de verificación del username

1. Establece conexión con la base de datos.
2. Prepara sentencia SQL con parámetros vinculados.
3. Ejecuta la consulta.
4. Retorna `true` si el usuario existe, `false` en caso contrario.

#### Seguridad

- Usa prepared statements para prevenir inyección SQL.
- Cierra conexiones después de cada consulta.

## Función `emailExists()`

### Propósito

La función `emailExists` se utiliza para comprobar de forma segura si el email de un usuario ya está registrado en la base de datos. Está diseñada para garantizar la seguridad y prevenir ataques comunes como inyección SQL.

### Implementación

```php
function emailExists($email) {
  // Conexión y lógica de verificación...
}
```

#### Parámetros

- `$email`: Dirección de email a verificar (string).

#### Proceso de verificación del email

1. Conexión a MySQL usando credenciales de `.configDB.php`.
2. Consulta preparada con `COUNT(*)`.
3. Vinculación de parámetros tipo string.
4. Retorno booleano basado en resultados.

#### Medidas Clave

- Mismo nivel de seguridad que `usernameExists()`.
- Aislamiento de consultas por función.

## Función `validatePassword()`

### Propósito

La función `validatePassword` se utiliza para validar de forma segura las credenciales de un usuario (nombre de usuario y contraseña) contra los datos almacenados en una base de datos. Está diseñada para garantizar la seguridad y prevenir ataques comunes como inyección SQL, fuerza bruta y ataques de tiempo.

### Implementación

```php
function validatePassword($username, $password) {
  // Conexión y lógica de verificación...
}
```

#### Parámetros

- **`$username`**: El nombre de usuario ingresado por el usuario (string).
- **`$password`**: La contraseña ingresada por el usuario en texto plano (string).

#### Proceso de verificación de la contraseña

1. Obtener la contraseña ingresada por el usuario del formulario de inicio de sesión.
2. Recuperar el hash almacenado de la contraseña del usuario desde la base de datos.
3. Utilizar la función `password_verify()` para comparar la contraseña ingresada con el hash almacenado.
4. Si `password_verify()` devuelve `true`, la contraseña es válida y se permite el acceso.
5. Si `password_verify()` devuelve `false`, la contraseña es inválida y se deniega el acceso.
6. Verificar si el hash necesita ser actualizado usando `password_needs_rehash()`.
7. Si se requiere actualización, generar un nuevo hash con `password_hash()` y actualizar la base de datos.
8. Manejar adecuadamente los errores y excepciones que puedan surgir durante el proceso.

#### Medidas Clave

- Usa prepared statements para prevenir inyección SQL.
- Utiliza `password_verify()` para comparación segura de contraseñas.
- Implementa actualización del hash para mantener estándares modernos.
- Maneja errores sin exponer información sensible al usuario.
- Garantiza cierre adecuado de conexiones y recursos abiertos.
