<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
</head>
<body>
  <h1>Login</h1>
  <form method="POST" action="../functions/login.php">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Log in</button>
  </form>
</body>
</html>

<?php
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

// Collect and sanitize input data
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$password = $_POST['password']; // Don't sanitize passwords

// Initialize an array to store validation errors
$errors = [];

// Validate username
if (!usernameExists($username)) {
  $errors['username'] = "Invalid username.";
}

// Validate password
if (!validatePassword($username, $password)) {
  $errors['password'] = "Invalid password.";
}

// Check if there are any validation errors
if (empty($errors)) {
  // If everything is OK, we call the function to log inthe user
  if (LoginUser($username, $password)) {
      echo "User Loged In successfully.";
      // Redirect to dashboard page
      header("Location: dashboard.php");
      session_start();
      $_SESSION['User_ID'] = $username['user_id'];
      exit();
  } else {
      echo "An error occurred during log in. Please try again later.";
  }
} else {
  // Display validation errors
  foreach ($errors as $field => $message) {
      echo "<p class='error'>$field: $message</p>";
  }
}
?>
