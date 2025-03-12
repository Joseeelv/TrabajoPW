<!DOCTYPE html>
<html>
<head>
  <title>RegisterUser</title>
</head>
<body>
  <h1>Register</h1>
  <form method="POST" action="register.php">
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
?>