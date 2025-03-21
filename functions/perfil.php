<?php
session_start();

require_once('./.configDB.php');
require_once('./validations.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
  die("Conexión fallida: " . mysqli_connect_error());
}

// Inicializa un array para almacenar errores de validación
$errors = [];

// Verifica si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
  $errors['user'] = "Usuario no autenticado";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION['user_id'];
  $updates = [];
  $types = "";
  $params = [];

  // Definir una lista de dominios de correo electrónico permitidos
  $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'example.com', 'test.com']; // Añade aquí los dominios que quieras permitir

  // Valida el email
  if (!empty($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = "Por favor, introduce una dirección de email válida.";
    } else {
      // Extraer el dominio del email
      $dominio = substr(strrchr($email, "@"), 1);
      if (!in_array($dominio, $dominiosPermitidos)) {
        $errors['email'] = "Por favor, utiliza un dominio de correo electrónico válido.";
      } elseif (emailExists($email)) { // Asegúrate de que esta función esté definida
        $errors['email'] = "Este email ya está registrado.";
      } else {
        $updates[] = "email = ?";
        $types .= "s";
        $params[] = $email;
      }
    }
  }

  // Valida la contraseña
  if (!empty($_POST['password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
      $errors['password'] = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
      $errors['password'] = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,}$/', $password)) {
      $errors['password'] = "La contraseña debe contener al menos una letra mayúscula, una minúscula, un número y un carácter especial.";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $updates[] = "user_secret = ?";
      $types .= "s";
      $params[] = $hashed_password;
    }
  }

  // Procesar imagen de perfil
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    // Validar que el archivo sea una imagen
    $file_info = getimagesize($_FILES['foto']['tmp_name']);
    if ($file_info === false) {
      $errors['foto'] = "Solo se permiten archivos JPG, JPEG y PNG.";
    } else {
      // Obtener la extensión del archivo
      $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

      // Validar la extensión del archivo
      if (!in_array($file_extension, $allowed_extensions)) {
        $errors['foto'] = "Solo se permiten archivos JPG, JPEG y PNG.";
      }
      // Validar el tamaño del archivo
      elseif ($_FILES['foto']['size'] > $max_file_size) {
        $errors['foto'] = "El archivo es demasiado grande. El tamaño máximo es 5 MB.";
      } else {
        // Generar un nombre único para la imagen basado en el username
        $new_filename = $_SESSION['username'] . '.' . $file_extension;

        // Ruta segura para guardar las imágenes
        $upload_path = "../assets/images/perfiles/" . $new_filename;

        // Verificar si existe una imagen previa que no sea default.jpg
        $query = "SELECT img_src FROM USERS WHERE user_id = ?";
        if ($stmt = mysqli_prepare($connection, $query)) {
          mysqli_stmt_bind_param($stmt, "i", $user_id);
          mysqli_stmt_execute($stmt);
          mysqli_stmt_bind_result($stmt, $old_img_src);
          mysqli_stmt_fetch($stmt);
          mysqli_stmt_close($stmt);

          // Si la imagen previa no es default.jpg y tiene un nombre diferente, eliminarla
          if ($old_img_src && $old_img_src !== 'default.jpg' && $old_img_src !== $new_filename) {
            $old_file_path = "../assets/images/perfiles/" . $old_img_src;
            if (file_exists($old_file_path)) {
              unlink($old_file_path);
            }
          }
        }

        // Verificar si la ruta de destino existe y tiene permisos de escritura
        if (!is_dir(dirname($upload_path))) {
          if (!mkdir(dirname($upload_path), 0755, true) && !is_dir(dirname($upload_path))) {
            $errors['foto'] = "No se pudo crear el directorio para subir la imagen.";
          }
        }

        // Mover el archivo a la ruta segura si no hay errores
        if (empty($errors['foto']) && is_writable(dirname($upload_path))) {
          if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
            // Actualizar campo img_src en la base de datos
            $updates[] = "img_src = ?";
            $types .= "s";
            $params[] = $new_filename;
          } else {
            $errors['foto'] = "Hubo un error al subir la imagen. Verifica los permisos del directorio.";
          }
        } else {
          $errors['foto'] = "El directorio no tiene permisos de escritura.";
        }
      }
    }
  }
}


// Si no hay errores y hay cambios que realizar
if (empty($errors) && !empty($updates)) {
  // Construir la consulta SQL dinámicamente
  $sql = "UPDATE USERS SET " . implode(", ", $updates) . " WHERE user_id= ?";
  $types .= "i";
  $params[] = intval($user_id);

  // Preparar y ejecutar la consulta
  if ($stmt = mysqli_prepare($connection, $sql)) {
    mysqli_stmt_bind_param($stmt, ...array_merge([$types], array_values($params)));
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success_message'] = "Perfil actualizado correctamente.";
      header("location: ./dashboard.php");
    } else {
      $_SESSION['register_errors']['database'] = "Error al actualizar el perfil: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
  } else {
    $_SESSION['register_errors']['database'] = "Error en la preparación de la consulta: " . mysqli_error($connection);
  }
}
$connection->close();
$_SESSION['register_errors'] = $errors;
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Actualizar Perfil</title>
  <link rel="stylesheet" href="../assets/css/styles.css">

</head>

<body>
  <?php include('./navbar.php'); ?>
  <main>
    <div class="container">
      <h2>Modificar Perfil</h2>
      <?php
      if (isset($_SESSION['success_message'])) {
        echo "<p class='success'>" . $_SESSION['success_message'] . "</p>";
        unset($_SESSION['success_message']);
      }
      if (!empty($_SESSION['register_errors'])) {
        foreach ($_SESSION['register_errors'] as $error) {
          echo "<p class='error'>$error</p>";
        }
        unset($_SESSION['register_errors']);
      }
      ?>

      <form id="updateProfileForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST"
        enctype="multipart/form-data">
        <div class="form-group">
          <label for="email">Nuevo Email:</label>
          <input type="email" id="email" name="email"
            placeholder="<?php echo htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-group">
          <label for="password">Nueva Contraseña:</label>
          <input type="password" id="password" name="password" placeholder="Nueva contraseña">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirmar Contraseña:</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
        </div>
        <div class="form-group">
          <label for="foto">Nueva foto de perfil:</label>
          <input type="file" id="foto" name="foto">
        </div>
        <button type="submit">Actualizar Perfil</button>
      </form>
    </div>
  </main>
  <?php include('./footer.php'); ?>
</body>

</html>