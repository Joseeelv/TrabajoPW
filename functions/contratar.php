<?php
session_start();
function Contratar($username, $pass, $email)
{
  $salario = 1000; // Salario del nuevo manager
  $connection = include('./conexion.php');
  $rol = "manager";

  mysqli_begin_transaction($connection);

  try {
    // Hashea la contraseña
    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

    // Inserta en la tabla USERS
    $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email, user_type) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
      throw new Exception("Error preparing USERS statement: " . $connection->error);
    }
    $stmt->bind_param("ssss", $username, $hashed_password, $email, $rol);
    if (!$stmt->execute()) {
      throw new Exception("Error al insertar en la tabla USERS: " . $stmt->error);
    }
    $user_id = $connection->insert_id;
    $stmt->close();

    // Inserta en la tabla MANAGERS
    $stmt = $connection->prepare("INSERT INTO MANAGERS (user_id, salary) VALUES (?, ?)");
    if ($stmt === false) {
      throw new Exception("Error preparing MANAGERS statement: " . $connection->error);
    }
    $stmt->bind_param("ii", $user_id, $salario);
    if (!$stmt->execute()) {
      throw new Exception("Error al insertar en la tabla MANAGERS: " . $stmt->error);
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

require_once("./validations.php");

// Comprueba si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Recolecta y sanitiza los datos de entrada
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $password = $_POST['password'];
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

  // Inicializa un array para almacenar errores de validación
  $errors = [];

  // Valida el nombre de usuario
  if (empty($username)) {
    $errors['username'] = "El nombre de usuario es obligatorio.";
  } elseif (strlen($username) < 3 || strlen($username) > 20) {
    $errors['username'] = "El nombre de usuario debe tener entre 3 y 20 caracteres.";
  } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = "El nombre de usuario solo puede contener letras, números y guiones bajos.";
  } elseif (usernameExists($username)) { // Asegúrate de que esta función esté definida en validations.php
    $errors['username'] = "Este nombre de usuario ya está en uso.";
  }

  // Valida la contraseña
  if (empty($password)) {
    $errors['password'] = "La contraseña es obligatoria.";
  } elseif (strlen($password) < 8) {
    $errors['password'] = "La contraseña debe tener al menos 8 caracteres.";
  } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,}$/', $password)) {
    $errors['password'] = "La contraseña debe contener al menos una letra mayúscula, una minúscula, un número y un carácter especial.";
  }

  // Valida el email
  if (empty($email)) {
    $errors['email'] = "El email es obligatorio.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Por favor, introduce una dirección de email válida.";
  } else {
    // Definir una lista de dominios de correo electrónico permitidos
    $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'example.com', 'test.com'];
    $dominio = substr(strrchr($email, "@"), 1);
    if (!in_array($dominio, $dominiosPermitidos)) {
      $errors['email'] = "Por favor, utiliza un dominio de correo electrónico válido.";
    } elseif (emailExists($email)) { // Asegúrate de que esta función esté definida
      $errors['email'] = "Este email ya está registrado.";
    }
  }

  // Comprueba si hay errores de validación
  if (empty($errors)) {
    try {
      Contratar($username, $password, $email);
      // Registro exitoso
      $_SESSION['success_message'] = "Manager registrado con éxito.";
      header("Location: ./employees.php");
      exit();
    } catch (Exception $e) {
      error_log("Error de registro: " . $e->getMessage());
      $errors['registration'] = "Error de registro: " . $e->getMessage();
    }
  }

  // Almacena los errores en la sesión para mostrarlos en el formulario
  $_SESSION['register_errors'] = $errors;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/manager.css">
</head>

<body>
  <?php include('./navbar.php'); ?>
  <main>
    <h1>Zona de contrato</h1>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <?php
      // Muestra errores si los hay
      if (isset($_SESSION['register_errors'])) {
        echo "<div class='error-container'>";
        foreach ($_SESSION['register_errors'] as $key => $error) {
          echo "<p class='error'>$error</p>";
        }
        echo "</div>";
        unset($_SESSION['register_errors']); // Limpia los errores después de mostrarlos
      }
      ?>
      <input type="text" name="username" placeholder="Nombre de usuario" required
        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      <div>
        <input type="password" name="password" id="password" placeholder="Contraseña" required>
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
      <input type="text" name="email" placeholder="Email" required
        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      <button type="submit" name="register">Registrarse</button>
    </form>
    <div id="manager-options">
      <a href="./contratar.php">Ver empleados</a>
      <a href="./despedir.php">Despedir</a>
      <a href="./transactions.php">Ver transacciones</a>
      <a href="./perfil.php">Ver perfil</a>
    </div>
    <?php 
      $connection = include('./conexion.php');
    try {
            // Obtener managers activos
            $stmt = $connection->prepare("SELECT USERS.*, MANAGERS.* FROM USERS INNER JOIN MANAGERS ON USERS.user_id = MANAGERS.user_id WHERE USERS.user_type = 'manager' AND MANAGERS.employee = 1");
            $stmt->execute();
            $activeManagers = $stmt->get_result();

            if ($activeManagers->num_rows > 0) {
                echo '<h2>Managers Activos</h2>';
                echo '<table>';
                echo '<thead><tr><th>Nombre</th><th>Email</th><th>Salario</th></tr></thead>';
                echo '<tbody>';
                while ($row = $activeManagers->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['salary']) . '€</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
                echo '</form>';
            } else {
                echo '<p>No se encontraron empleados activos.</p>';
            }

            // Obtener managers despedidos
            $stmt = $connection->prepare("SELECT USERS.*, MANAGERS.* FROM USERS INNER JOIN MANAGERS ON USERS.user_id = MANAGERS.user_id WHERE USERS.user_type = 'manager' AND MANAGERS.employee = 0");
            $stmt->execute();
            $inactiveManagers = $stmt->get_result();

            if ($inactiveManagers->num_rows > 0) {
                echo '<h2>Managers Despedidos</h2>';
                echo '<form method="POST" action="./recontratar.php">';
                echo '<table>';
                echo '<thead><tr><th>Nombre</th><th>Email</th><th>Salario</th><th>Recontratar</th></tr></thead>';
                echo '<tbody>';
                while ($row = $inactiveManagers->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['salary']) . '€</td>';
                    echo '<td><input type="checkbox" name="recontratar[]" value="' . htmlspecialchars($row['user_id']) . '"></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
                echo '<button type="submit">Recontratar seleccionados</button>';
                echo '</form>';
            } else {
                echo '<p>No hay managers despedidos.</p>';
            }

        } catch (Exception $e) {
            echo '<p class="error">Error al obtener los empleados: ' . htmlspecialchars($e->getMessage()) . '</p>';
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            $connection->close();
        }
        ?>
  </main>
  <script src="../assets/js/password-strength-meter.js"></script>
  <?php include('./footer.php'); ?>
</body>

</html>