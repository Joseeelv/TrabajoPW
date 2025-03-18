<?php
session_start(); // Start the session
// Function to login
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

// Import a validation library or define validation functions
require_once('validations.php');

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Collect and sanitize input data
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $password = $_POST['password']; // Don't sanitize passwords

  // Initialize an array to store validation errors
  $errors = [];

  // Validate username
  if (!usernameExists($username)) {
      $errors['username'] = "Username does not exist.";
  }

  // If username exists, then validate password
  if (empty($errors)) {
      $user = LoginUser($username, $password);
      if ($user === false) {
          $errors['password'] = "Invalid password.";
      }
  }

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
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
		<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
  <h1>Login</h1>
  <?php
  // Display errors if any
  if (isset($_SESSION['login_errors'])) {
      foreach ($_SESSION['login_errors'] as $error) {
          echo "<p style='color: red;'>$error</p>";
      }
      unset($_SESSION['login_errors']); // Clear the errors after displaying
  }
  ?>
  <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Log in</button>
  </form>
  <p>Don't have an account? <a href="register.php">Register</a></p>
</body>
</html>
