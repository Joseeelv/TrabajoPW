<?php

function usernameExists($username){
    // Lógica para verificar si el nombre de usuario ya existe
    $connection = include('./conexion.php');
    if (!$connection) {
        die("Conexión fallida: " . mysqli_connect_error());
    }
    // Prevenir inyección SQL
    $stmt = $connection->prepare("SELECT COUNT(*) FROM USERS WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];

    $stmt->close();
    $connection->close();
    return $count > 0; // Es verdadero si el nombre de usuario existe
}


function emailExists($email)
{
    // Lógica para verificar si el correo electrónico ya existe
    $connection = include('./conexion.php');
    if (!$connection) {
        die("Conexión fallida: " . mysqli_connect_error());
    }
    // Prevenir inyección SQL
    $stmt = $connection->prepare("SELECT COUNT(*) FROM USERS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    $connection->close();

    return $count > 0; // Es verdadero si el correo electrónico existe
}

function validatePassword($username, $password){
    // Conexión a la base de datos
    $connection = include('./conexion.php');
    if (!$connection) {
        error_log("Conexión fallida: " . mysqli_connect_error());
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

class Validator {
    private const ALLOWED_DOMAINS = [
        'gmail.com', 'hotmail.com', 'outlook.com', 
        'yahoo.com', 'example.com', 'test.com'
    ];

    public static function validateUsername(string $username): array {
        $errors = [];

        if (empty($username)) {
            $errors[] = "El nombre de usuario es obligatorio.";
        }
        if (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = "El nombre de usuario debe tener entre 3 y 20 caracteres.";
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "El nombre de usuario solo puede contener letras, números y guiones bajos.";
        }
        if (usernameExists($username)) {
            $errors[] = "Este nombre de usuario ya está en uso.";
        }

        return $errors;
    }

    public static function validatePassword(string $password): array {
        $errors = [];

        if (empty($password)) {
            $errors[] = "La contraseña es obligatoria.";
        }

        $rules = [
            'longitud' => [
            strlen($password) >= 8, 
            "8 caracteres mínimo."
            ],
            'minúscula' => [
            preg_match('/[a-z]/', $password), 
            "Letra minúscula."
            ],
            'mayúscula' => [
            preg_match('/[A-Z]/', $password), 
            "Letra mayúscula."
            ],
            'número' => [
            preg_match('/\d/', $password), 
            "Números."
            ],
            'especial' => [
            preg_match('/[@$!%*?&\_-]/', $password), 
            "Caracteres especiales (@$!%*?&_-)."
            ],
        ];

        $errorMessages = [];
        foreach ($rules as [$valid, $message]) {
            if (!$valid) $errorMessages[] = $message;
        }
        if (!empty($errorMessages)) {
            $errors[] = "La contraseña debe cumplir con los siguientes requisitos:";
            $errors = array_merge($errors, $errorMessages);
        }

        return $errors;
    }

    public static function validateEmail(string $email): array {
        $errors = [];

        if (empty($email)) {
            $errors[] = "El email es obligatorio.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Por favor, introduce una dirección de email válida.";
        } else {
            $domain = substr(strrchr($email, "@"), 1);
            if (!in_array($domain, self::ALLOWED_DOMAINS)) {
                $errors[] = "Por favor, utiliza un dominio de correo electrónico válido.";
            }
            if (emailExists($email)) {
                $errors[] = "Este email ya está registrado.";
            }
        }

        return $errors;
    }
}
