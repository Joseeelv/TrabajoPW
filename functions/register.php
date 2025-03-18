<?php
session_start(); // Start the session

// ************** SECURITY CONFIGURATION ************** //
const SECURITY = [
	'csrf_token_expire' => 3600, // 1 hora
	'rate_limit' => 5, // Intentos máximos por hora
	'password_min_strength' => 3 // Nivel de seguridad de contraseña (0-4)
];

// ************** CSRF PROTECTION ************** //
function generateCsrfToken()
{
	if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		$_SESSION['csrf_token_expire'] = time() + SECURITY['csrf_token_expire'];
	}
	return $_SESSION['csrf_token'];
}

function validateCsrfToken($token)
{
	return hash_equals($_SESSION['csrf_token'], $token) && time() < $_SESSION['csrf_token_expire'];
}

// Headers to prevent XSS attacks
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");

// Function to register a new user
function RegisterUser($username, $pass, $email)
{
	require_once('.configDB.php');
	$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
	if (!$connection) {
		die("Connection failed: " . mysqli_connect_error());
	}

	// Hash the password
	$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

	// Insert into USERS table
	$stmt = $connection->prepare("INSERT INTO USERS (username, user_secret, email) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $username, $hashed_password, $email);
	if (!$stmt->execute()) {
		throw new Exception("Error inserting into USERS table: " . $stmt->error);
	}
	$user_id = $connection->insert_id;
	$stmt->close();

	// Insert into CUSTOMERS table
	$stmt = $connection->prepare("INSERT INTO CUSTOMERS (user_id, customer_address) VALUES (?, ?)");
	$address = "No address";
	$stmt->bind_param("is", $user_id, $address);
	if (!$stmt->execute()) {
		throw new Exception("Error inserting into CUSTOMERS table: " . $stmt->error);
	}
	$stmt->close();
	$connection->close();
}

// Import a validation library or define validation functions
require_once('validations.php');

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// Collect and sanitize input data
	$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
	$password = $_POST['password'];
	$email = trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'));

	// Initialize an array to store validation errors
	$errors = [];

	// Collect and sanitize input data
	$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
	$password = $_POST['password'];
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
	} elseif (usernameExists($username)) { // Very if the username already exists
		$errors['username'] = "This username is already taken.";
	}

	// Validate password
	if (empty($password)) {
		$errors['password'] = "Password is required.";
	} elseif (strlen($password) < 8) {
		$errors['password'] = "The password must be at least 8 characters long.";
	} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,}$/', $password)) {
		$errors['password'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
	}

	// Validate email
	if (empty($email)) {
		$errors['email'] = "Email is required.";
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors['email'] = "Please enter a valid email address.";
	} elseif (emailExists($email)) { // Verify if the email already exists
		$errors['email'] = "This email is already registered.";
	}

	// ************** MAIN EXECUTION FLOW ************** //
	header("Strict-Transport-Security: max-age=63072000; includeSubDomains");
	header("X-Content-Type-Options: nosniff");
	header("X-Frame-Options: DENY");

		// Validación CSRF
		if (!validateCsrfToken($_POST['csrf_token'])) {
			throw new Exception("Invalid CSRF token");
		}
		//Limitar intentos
		// $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
		// if ($_SESSION['attempts'] > 5) {
		// 	die("Demasiados intentos. Intenta más tarde.");
		// }

		// Check for validation errors
		if (empty($errors)) {
			try {
				RegisterUser($username, $password, $email);
				// Successful registration
				$_SESSION['success_message'] = "User registered successfully.";
				header("Location: login.php");
				exit();
			} catch (Exception $e) {
				error_log("Registration error: " . $e->getMessage());
				$errors['registration'] = "An error occurred during registration. Please try again later.";
			}
		}

	// Store errors in session to display them on the form
	$_SESSION['register_errors'] = $errors;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
		<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <h1>Register</h1>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
				<?php
				// Display errors if any
				if (isset($_SESSION['register_errors'])) {
						foreach ($_SESSION['register_errors'] as $error) {
								echo "<p style='color: red;'>$error</p>";
						}
						unset($_SESSION['register_errors']); // Clear the errors after displaying
				}
				?>
				<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="text" name="username" placeholder="Username" required>
        <div>
            <input type="password" name="password" id="password" placeholder="Password" required>
            
            <!-- Barra de fuerza -->
            <div class="password-strength-meter">
                <div class="password-strength-meter-fill"></div>
            </div>

            <!-- Checklist -->
            <ul class="password-checklist">
                <li id="length">At least 8 characters long</li>
                <li id="uppercase">Contains uppercase letter</li>
                <li id="lowercase">Contains lowercase letter</li>
                <li id="number">Contains number</li>
                <li id="special">Contains special character</li>
            </ul>
        </div>
        
        <input type="text" name="email" placeholder="Email" required>
        <button type="submit" name="register">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
    <script src="../assets/password-strength-meter.js"></script>
</body>
</html>