<?php
session_start();

function Contratar($username, $pass)
{
  $salario = 1000; // Salario por defecto
  $connection = include('./conexion.php');
  $rol = "manager";
  $email = $username . "@donerkebab.com";

  mysqli_begin_transaction($connection);

  try {
    // Hashear contraseña
    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

    // Insertar en USERS
    $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email, user_type) VALUES (?, ?, ?, ?)");
    if ($stmt === false) throw new Exception("Error preparando USERS: " . $connection->error);
    $stmt->bind_param("ssss", $username, $hashed_password, $email, $rol);
    if (!$stmt->execute()) throw new Exception("Error USERS: " . $stmt->error);
    $user_id = $connection->insert_id;
    $stmt->close();

    // Insertar en MANAGERS
    $stmt = $connection->prepare("INSERT INTO MANAGERS (user_id, salary) VALUES (?, ?)");
    if ($stmt === false) throw new Exception("Error preparando MANAGERS: " . $connection->error);
    $stmt->bind_param("ii", $user_id, $salario);
    if (!$stmt->execute()) throw new Exception("Error MANAGERS: " . $stmt->error);
    $stmt->close();

    mysqli_commit($connection);
    return true;
  } catch (Exception $e) {
    mysqli_rollback($connection);
    throw $e;
  } finally {
    $connection->close();
  }
}

require_once("./validations.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $password = $_POST['password'];
  $errors = [];

  // Validaciones
  $username_errors = Validator::validateUsername($username);
  $password_errors = Validator::validatePassword($password);

  if (!empty($username_errors)) $errors['username'] = implode(" ", $username_errors);
  if (!empty($password_errors)) $errors['password'] = implode(" ", $password_errors);

  if (empty($errors)) {
    try {
      if (Contratar($username, $password)) {
        $_SESSION['success_message'] = "Manager registrado con éxito";
        header("Location: ./employees.php");
        exit();
      }
    } catch (Exception $e) {
      error_log("Error de registro: " . $e->getMessage());
      $errors['registration'] = "Error al crear el usuario: " . $e->getMessage();
    }
  }

  $_SESSION['register_errors'] = $errors;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contratación</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/register.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
  <?php include('./navbar.php'); ?>
  <main>
    <h1>Zona de contrato</h1>
    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
      <div>
      <input type="text" name="username" placeholder="Nombre de usuario" required
      value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        <input type="password" name="password" id="password" placeholder="Contraseña" required>
        <div class="password-strength-meter">
          <div class="password-strength-meter-fill"></div>
        </div>
        <ul class="password-checklist">
          <li id="length">Al menos 8 caracteres</li>
          <li id="uppercase">Contiene mayúscula</li>
          <li id="lowercase">Contiene minúscula</li>
          <li id="number">Contiene número</li>
          <li id="special">Contiene carácter especial</li>
        </ul>
      </div>

      <button type="submit" name="register">Registrar manager</button>

      <?php if (isset($_SESSION['register_errors'])): ?>
        <div class='error-container'>
          <?php foreach ($_SESSION['register_errors'] as $error): ?>
            <p class='error'><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['register_errors']); ?>
      <?php endif; ?>
    </form>

    <?php
    $connection = include('./conexion.php');
    try {
      // Managers activos
      $stmt = $connection->prepare("SELECT USERS.*, MANAGERS.* FROM USERS 
                INNER JOIN MANAGERS ON USERS.user_id = MANAGERS.user_id 
                WHERE USERS.user_type = 'manager' AND MANAGERS.employee = 1");
      $stmt->execute();
      $activeManagers = $stmt->get_result();

      if ($activeManagers->num_rows > 0): ?>
        <h2>Managers Activos</h2>
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Email</th>
              <th>Salario</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $activeManagers->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['salary']) ?>€</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No se encontraron empleados activos.</p>
      <?php endif;

      // Managers despedidos
      $stmt = $connection->prepare("SELECT USERS.*, MANAGERS.* FROM USERS 
                INNER JOIN MANAGERS ON USERS.user_id = MANAGERS.user_id 
                WHERE USERS.user_type = 'manager' AND MANAGERS.employee = 0");
      $stmt->execute();
      $inactiveManagers = $stmt->get_result();

      if ($inactiveManagers->num_rows > 0): ?>
        <h2>Managers Despedidos</h2>
        <form method="POST" action="./recontratar.php">
          <table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Salario</th>
                <th>Recontratar</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $inactiveManagers->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['username']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['salary']) ?>€</td>
                  <td><input type="checkbox" name="recontratar[]" value="<?= $row['user_id'] ?>"></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
          <button type="submit">Recontratar seleccionados</button>
        </form>
      <?php else: ?>
        <p>No hay managers despedidos.</p>
    <?php endif;
    } catch (Exception $e) {
      echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    } finally {
      $connection->close();
    }
    ?>
  </main>
  <script src="../assets/js/password-strength-meter.js"></script>
  <?php include('./footer.php'); ?>
</body>

</html>