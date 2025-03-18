<?php

// Set secure cookie parameters
session_set_cookie_params([
  'lifetime' => 3600,
  'path' => '/',
  'domain' => $_SERVER['HTTP_HOST'],
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Strict'
]);

session_start(); // Start the session
// Security configuration
const SECURITY = [
  'max_attempts' => 5,
  'lockout_time' => 1800, // 30 minutes
  'csrf_token_expire' => 3600 // 1 hour
];

// Function to generate CSRF token
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

// Function to log in the user
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

// Function to log failed login attempts (without database queries)
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

// Function to check if the user is locked (based on session data)
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

// Import a validation library or define validation functions
require_once('validations.php');

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Validate CSRF token
  if (!validateCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token");
  }

  // Collect and sanitize input data
  $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
  $password = $_POST['password']; // Don't sanitize passwords

  // Initialize an array to store validation errors
  $errors = [];

  // Check if the user is locked
  if (isUserLocked($username)) {
    $errors['username'] = "Account is temporarily locked. Please try again later.";
  } else {
    // Validate username
    if (!usernameExists($username)) {
      $errors['username'] = "Username does not exist.";
    }

    // If username exists, then validate password
    if (empty($errors)) {
      $user = LoginUser($username, $password);
      if ($user === false) {
        $errors['password'] = "Invalid password.";
        logFailedAttempt($username);
      }
    }
  }

  // Check for validation errors
  if (empty($errors)) {
    // Successful login
    session_regenerate_id(true); // Regenerate session ID
    $_SESSION['success_message'] = "User logged in successfully.";
    $_SESSION['User_ID'] = $user['user_id'];
    $_SESSION['last_activity'] = time(); // For automatic session renewal
    if( $_SESSION['User_ID'] == 1){
      header("Location: admin.php"); // Redirect to the admin page
    } else {
      header("Location: dashboard.php"); // Redirect to the dashboard
    }
    exit();
  } else {
    // Store errors in session to display them on the form
    $_SESSION['login_errors'] = $errors;
    header("Location: login.php"); // Redirect back to the login page
    exit();
  }
}

// Automatic session renewal
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
  session_regenerate_id(true);
  $_SESSION['last_activity'] = time();
}

// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <button type="submit" name="login">Log in</button>
  </form>
  <p>Don't have an account? <a href="register.php">Register</a></p>
</body>

</html>