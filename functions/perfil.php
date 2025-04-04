<?php
session_start();


require_once('./validations.php');

// Obtener conexión
$connection = require_once('./conexion.php');
if (!$connection) {
  die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

function UpdateProfile($connection, $pass, $email, $address, $image)
{
  mysqli_begin_transaction($connection);
  try {
    // Validar y encriptar la contraseña si no está vacía
    $hashed_password = null;
    if (!empty($pass)) {
      $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
    }

    // Actualizar la tabla USERS
    $stmt = $connection->prepare("UPDATE USERS SET 
                                      user_secret = COALESCE(?, user_secret),
                                      img_src = COALESCE(?, img_src)
                                      WHERE user_id = ?");
    $stmt->bind_param("ssi", $hashed_password, $image, $_SESSION['user_id']);

    if (!$stmt->execute()) {
      throw new Exception("Error al actualizar USERS: " . $stmt->error);
    }
    $stmt->close();

    // Solo los CUSTOMERS pueden modificar su dirección
    if ($_SESSION['user_type'] === 'customer') {
      // Actualizar la tabla CUSTOMERS solo si la dirección no está vacía
      if (!empty($address)) {
        $stmt = $connection->prepare("UPDATE CUSTOMERS SET customer_address = ? WHERE user_id = ?");
        $stmt->bind_param("si", $address, $_SESSION['user_id']);
        if (!$stmt->execute()) {
          throw new Exception("Error al actualizar CUSTOMERS: " . $stmt->error);
        }
        $stmt->close();
      }
    }

    mysqli_commit($connection);
  } catch (Exception $e) {
    mysqli_rollback($connection);
    throw $e;
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Obtener los datos del formulario
  $password = $_POST['password'];
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $address = trim(htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8'));
  $image = isset($_FILES['foto']) ? $_FILES['foto'] : null;

  $errors = [];

  // Validación contraseña
  if (!empty($password)) {
    $password_errors = Validator::validatePassword($password);
    if (!empty($password_errors)) {
      $errors['password'] = implode(" ", $password_errors);
    }
    //Ambas contraseñas deben coincidir
    if (isset($_POST['confirm_password']) && $_POST['confirm_password'] !== $password) {
      $errors['confirm_password'] = "Las contraseñas no coinciden.";
    }
  }

  // Si es admin o manager, no permitir modificar el email
  if ($_SESSION['user_type'] === 'customer') {
    // Validación del email
    if (!empty($email)) {
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Introduce un email válido.";
      } else {
        $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'example.com', 'test.com'];
        $dominio = substr(strrchr($email, "@"), 1);
        if (!in_array($dominio, $dominiosPermitidos)) {
          $errors['email'] = "Usa un dominio de correo válido.";
        } else {
          // Verificar si el email ya está registrado
          $query = "SELECT COUNT(*) FROM USERS WHERE email = ? AND user_id != ?";
          $stmt = $connection->prepare($query);
          $stmt->bind_param("si", $email, $_SESSION['user_id']);
          $stmt->execute();
          $stmt->bind_result($email_count);
          $stmt->fetch();
          $stmt->close();

          if ($email_count > 0) {
            $errors['email'] = "Este email ya está registrado.";
          }
        }
      }
    }
  } else {
    // Si es admin o manager, ignorar el email
    $email = null;
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
          mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
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
          if (!mkdir(dirname($upload_path), 0007, true) && !is_dir(dirname($upload_path))) {
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
            $_SESSION['img_src'] = $new_filename;
          } else {
            $errors['foto'] = "Hubo un error al subir la imagen. Verifica los permisos del directorio.";
          }
        } else {
          $errors['foto'] = "El directorio no tiene permisos de escritura.";
        }
      }
    }
  }

  // Si no hay errores, actualizar perfil
  if (empty($errors)) {
    UpdateProfile($connection, $password, $email, $address, $new_filename);
    $_SESSION['success_message'] = "Perfil actualizado correctamente.";
    switch ($_SESSION['user_type']) {
      case 'admin':
        header("Location: admin.php");
        break;
      case 'customer':
        header("Location: dashboard.php");
        break;
      case 'manager':
        header("Location: manager_index.php");
        break;
      default:
        header("Location: index.php");
    }
    exit();
  }

  $_SESSION['register_errors'] = $errors;
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">

  <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Actualizar Perfil</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/perfil.css">
  <link rel="stylesheet" href="../assets/css/register.css">
  <script src="../assets/js/previewFoto.js" defer></script>
</head>

<body>
  <?php include('./navbar.php'); ?>

  <main>
    <h1>Modificar Perfil</h1>

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
    <form id="updateProfileForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST"
      enctype="multipart/form-data">
      <div class="form-group" <?php if ($_SESSION['user_type'] !== 'customer') echo 'style="display:none;"'; ?>>
        <label for="email">Nuevo Email:</label>
        <input type="email" id="email" name="email"
          placeholder="<?php echo htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div>
        <input type="password" name="password" id="password" placeholder="Contraseña">
        <!-- Barra de fortaleza -->
        <div class="password-strength-meter">
          <div class="password-strength-meter-fill"></div>
        </div>
        <!-- Lista de verificación -->
        <ul class="password-checklist">
          <li id="length">Al menos 8 caracteres de longitud</li>
          <li id="uppercase">Contiene letra mayúscula</li>
          <li id="lowercase">Contiene letra minúscula</li>
          <li id="number">Contiene número</li>
          <li id="special">Contiene carácter especial</li>
        </ul>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirmar Contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
      </div>
      <?php echo htmlspecialchars("hola"); ?>
      <?php if ($_SESSION['user_type'] === 'customer'): ?>
        <div class="form-group">
          <label for="address">Nueva dirección:</label>
          <input type="text" id="address" name="address" placeholder="Nueva dirección">
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label for="foto">Nueva foto de perfil:</label>
        <input type="file" id="foto" name="foto" accept="image/*">
        <img id="previewImage" src="" alt="Vista previa" style="display: none; width: 200px; height: auto;">

      </div>
      <button type="submit">Actualizar Perfil</button>
    </form>
  </main>

  <?php include('./footer.php'); ?>
  <script src="../assets/js/password-strength-meter.js"></script>
</body>

</html>