<!DOCTYPE html>
<html>
<head>
  <title>RegisterUser</title>
</head>
<body>
  <h1>Register</h1>
  <form method="POST" action="../functions/register.php">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="email" name="email" placeholder="Email" required>
    <button type="submit" name="register">Register</button>
  </form>
</body>
</html>

<?php
// Function to register a new user
function RegisterUser($username, $pass, $email) {
  require_once('.configDB.php');
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  if (!$connection) {
      die("Connection failed: " . mysqli_connect_error());
  }

    // Hash the password
    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

  // Using prepared statement:
  // Insert into USERS table
  $stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $hashed_password, $email);
  $stmt->execute();
  $user_id = $connection->insert_id;
  $stmt->close();

  // Insert into CUSTOMERS table
  $stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
  $address = "No address";
  $stmt->bind_param("is", $user_id, $address);
  $stmt->execute();
}

// Import a validation library or define validation functions
require_once('validations.php');

// Collect and sanitize input data
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$password = $_POST['password']; // Don't sanitize passwords
$email = trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'));

// Initialize an array to store validation errors
$errors = [];

// Validate username
if (empty($username)) {
    $errors['username'] = "Username is required.";
} elseif (strlen($username) < 3 || strlen($username) > 20) {
    $errors['username'] = "Username must be between 3 and 20 characters long.";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = "Username can only contain letters, numbers, and underscores.";
} elseif (usernameExists($username)) { // Assume this function checks if the username already exists in the database
    $errors['username'] = "This username is already taken.";
}

// Validate password
if (empty($password)) {
    $errors['password'] = "Password is required.";
} elseif (strlen($password) < 8) {
    $errors['password'] = "The password must be at least 8 characters long.";
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    $errors['password'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
}

// Validate email
if (empty($email)) {
    $errors['email'] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Please enter a valid email address.";
} elseif (emailExists($email)) { // Assume this function checks if the email already exists in the database
    $errors['email'] = "This email is already registered.";
}

// Check if there are any validation errors
if (empty($errors)) {
    // If everything is OK, we call the function to register the user
    if (RegisterUser($username, $password, $email)) {
        echo "User registered successfully.";
        // Redirect to login page or send confirmation email
        header("Location: login.php");
        exit();
    } else {
        echo "An error occurred during registration. Please try again later.";
    }
} else {
    // Display validation errors
    foreach ($errors as $field => $message) {
        echo "<p class='error'>$field: $message</p>";
    }
}
?>