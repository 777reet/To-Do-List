<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = ""; 
$dbname = "todolist";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id = trim($_POST['id']);
    $password = trim($_POST['password']);

    if (empty($id) || empty($password)) {
        echo "<p style='color:red;'>Please fill in all the details.</p>";
    } elseif (!filter_var($id, FILTER_VALIDATE_EMAIL)) {
        echo "<p style='color:red;'>Please enter a valid email as ID.</p>";
    } else {
        // Use prepared statements
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Assuming your passwords are stored hashed in DB,
            // replace the next line with password_verify:
            // if (password_verify($password, $user['password'])) {
            
            // If passwords are plain text (not recommended), just compare:
            if ($password === $user['password']) {
                // Successful login: set session variable
                $_SESSION['user_id'] = $user['id'];  // or user ID if separate field
                
                header("Location: http://localhost/todolist/index.php");
                exit();
            } else {
                echo "<p style='color:red;'>Invalid ID or password.</p>";
            }
        } else {
            echo "<p style='color:red;'>Invalid ID or password.</p>";
        }

        $stmt->close();
    }
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
