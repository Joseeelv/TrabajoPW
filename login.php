<?php
  //Connection to the database
  $connection = new mysqli(<host>, <username>, <password>, <database>); //Replace the values with your own
  if($connection -> connect_error){
    die("Connection failed: " . $connection -> connect_error);
  }
  //Function to register a new user
  function RegisterUser($username, $pass){
    global $connection;
    //Hash the password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    $role = "costumer";

    //Prevents SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    $stmt->execute();
    $stmt->close();   
  }

  //Function to verify
  function Login($username, $pass){
    global $connection;
    //Prevents SQL injection
    $stmt = $conn->prepare("SELECT password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    if($row = $result->fetch_assoc()){
      if(password_verify($pass, $row['password'])){
        return ['id' => $row['id'], 'rol' => $row['rol']];
      }
    }
  }

  //Example of use
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['register'])){
      RegisterUser($_POST['username'], $_POST['password']);
      echo "Usuario registrado con éxito como customer";
    }else{
      $user = Login($_POST['username'], $_POST['password']);
      if($user){
        session_start();
        $_SESSION['id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
      }
    }
  }
?>