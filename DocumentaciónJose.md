# Sistema de Registro de Usuarios
Esta documentación describe un sistema de registro de usuarios basado en PHP. El sistema incluye una función para registrar nuevos usuarios y un proceso de validación exhaustivo para la entrada de datos del usuario.

## Componentes Principales
Esta función *RegisterUser* hace uso de:
- Validación y saneamiento de la entrada.
- Manejo de errores.

## Propósito
El propósito principal de esta función es registrar un nuevo usuario en la base de datos insertando su información en las tablas `USERS` y `CUSTOMERS`.

En la función encontramos los parámetros `$username`, `$pass` y `$email` (campos imprescindibles para la creación de un usuario).

```php
function RegisterUser($username, $pass, $email) {
  //Implementación de la función.
}
```

### Inserción en la tabla USERS
```php
$stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
```
### Inserción en la tabla CUSTOMERS
```php
$stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
```

## Funcionalidad
1. Nos conectamos a la base de datos.
2. Hasheamos la contraseña que inserta el usuario en el campo "*password*" (esto se realiza para una mayor seguridad).
3. Inserta los datos del usuario en la tabla `USERS`.
4. Obtiene el ID del nuevo usuario (Clave Primaria de la tabla `CUSTOMERS`).
5. Inserta una entrada por defecto en la tabla `CUSTOMERS` para el nuevo usuario.

### Implementación de la función RegisterUser
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

## Medidas de seguridad
En esta función, al ser un registro tenemos que tener mucho cuidado con los datos que se insertan, por tanto se ha realizado las siguientes medidas de seguridad:
- **Prevención de Inyección SQL**: Se utilizan sentencias preparadas (prepared statements) para todas las consultas SQL. Esto separa los datos de la estructura de la consulta, evitando así la inyección SQL.
- **Se hashean las contraseñas**: Se almacenan las contraseñas hasheadas para una mayor seguridad y prevenir ataques de robo de credenciales.
- **Prevención contra XSS**: Se hace uso de funciones específicas para el saneo de algunos campos con el fin de prevenir de ataques **XSS persistente**, esto se hace mediante la validación con `htmlspecialchars`:
  ```php
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $email = trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'));
  ```

### Validaciones realizadas
Mediante el uso de funciones de validación se comprueban lo siguiente:
#### Validación del Nombre de Usuario
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

#### Validación de la Contraseña
- Obligatorio.
- Longitud mínima de 8 caracteres.
- Debe contener mayúsculas, minúsculas, números y caracteres especiales.

```php
// Validate password
  if (empty($password)) {
      $errors['password'] = "Password is required.";
  } elseif (strlen($password) < 8) {
      $errors['password'] = "The password must be at least 8 characters long.";
  } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])  [A-Za-z\d@$!%*?&]{8,}$/', $password)) {
      $errors['password'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
}
```

#### Validación del Correo Electrónico
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

### Manejo de Errores
- Se almacena los errores en un array `$errors`.
- Muestra mensajes de error específicos para cada fallo de validación.
- Impide el registro si hay errores presentes.

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

## Uso del sistema
1. El usuario envía el formulario de registro.
2. El sistema valida y sanea la entrada.
3. Si la validación tiene éxito, se llama a `RegisterUser`.
4. En caso de registro exitoso, el usuario es redirigido a la página de inicio de sesión.
5. Si el registro falla, se muestra un mensaje de error.

# Sistema de Inicio de Sesión

## Componentes Principales
1. Formulario HTML para inicio de sesión.
2. Función `LoginUser` para verificar credenciales.
3. Validación de entrada de datos.
4. Manejo de sesiones y redirecciones.

## Propósito
El propósito principal de esta función es verificar el inicio de sesión de los usuario de la base de datos obteniendo la información de la tabla `USERS`.

En la función encontramos los parámetros `$username`, `$pass`. Estos campos serán validados respecto al contenido de la base de datos para garantizar el acceso de las funcionalidades a aquellos usuarios registrados en el sistema.

```php
function LoginUser($username, $pass) {
  // Implementación de la función.
}
```
### Obtención de las credenciales de la tabla USERS
```php
  $stmt = $connection->prepare("SELECT user_id, user_secret FROM USERS WHERE username = ?");
```
## Funcionalidad
1. Se realiza la conexión a la base de datos.
2. Búsqueda del usuario mediante una sentencia preparada.
3. Verificación de existencia del usuario.
4. Comparación de hash de contraseña con `password_verify`.
5. Retorno de datos del usuario o false en caso de fallo.

### Implementación de la función LoginUser
```php
function LoginUser($username, $pass) {
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

## Medidas de seguridad
Se realizan las siguientes medidas de seguridad con el fin de que el inicio de sesión sea lo más seguro posible, para ello se llevan a cabo las siguientes medidas:
- **Prevención de Inyección SQL**: Se utilizan sentencias preparadas (prepared statements) para todas las consultas SQL. Esto separa los datos de la estructura de la consulta, evitando así la inyección SQL.
- **Protección de Credenciales**: Verificación con hash `BCrypt`.
- **Gestión de Sesiones**: Uso de `session_start()` y variables de sesión.
- **Prevención contra XSS**: Se hace uso de funciones específicas para el saneo de algunos campos con el fin de prevenir de ataques **XSS persistente**, esto se hace mediante la validación con `htmlspecialchars`:
  ```php
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  ```
### Validaciones realizadas
Mediante el uso de funciones de validación se comprueban lo siguiente:
#### Validación del Nombre de Usuario
- Obligatorio.
- Se realiza sanitización.

```php
// Validate username
if (!usernameExists($username)) {
  $errors['username'] = "Username does not exist.";
}
```

#### Validación de la Contraseña
- Obligatorio.
- Verificación contra hash almacenado.

```php
// If username exists, then validate password
if (empty($errors)) {
  $user = LoginUser($username, $password);
  if ($user === false) {
      $errors['password'] = "Invalid password.";
  }
}
```

### Manejo de Errores
- Array `$errors` para recolección de fallos.
- Mensajes específicos por campo.
Redirección condicional según resultado.
- Mensajes genéricos para evitar información sensible:
```php
// Initialize an array to store validation errors
$errors = [];
// Check for validation errors
if (empty($errors)) {
  // Successful login
  $_SESSION['success_message'] = "User logged in successfully.";
  $_SESSION['User_ID'] = $user['user_id'];
  header("Location: dashboard.php"); // Redirect to the dashboard
  exit();
} else {
  // Store errors in session to display them on the form
  $_SESSION['login_errors'] = $errors;
  header("Location: login.php"); // Redirect back to the login page
  exit();
}
```

## Uso del sistema
1. El usuario envía formulario.
2. El Sistema sanitiza y valida inputs proporcionados por el usuario.
3. Si hay errores: muestra mensajes específicos.
4. Si no hay errores:
  - Intenta autenticar con `LoginUser`.
  - Éxito: Redirige a `dashboard.php`.
  - Falla: Muestra error genérico.

## Mejoras Recomendadas
1. Regeneración de ID de Sesión:
```php
session_regenerate_id(true);
```
2. Límite de Intentos: Prevenir fuerza bruta
3. Cookies Seguras:
```php
session_set_cookie_params([
    'lifetime' => 3600,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```
4. Monitorización de Actividad:
- Registrar intentos fallidos.
- Alertar sobre actividades sospechosas.

## Consideraciones de Seguridad Adicionales
1. Uso obligatorio de HTTPS.
2. Implementación de doble factor de autenticación.
3. Renew automático de sesiones.
4. Validación de origen de peticiones (CSRF tokens).

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
