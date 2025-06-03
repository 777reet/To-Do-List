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
        exit();
    }

    if (!filter_var($id, FILTER_VALIDATE_EMAIL)) {
        echo "<p style='color:red;'>Please enter a valid email as ID.</p>";
        exit();
    }

    // Checks if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("s", $id);
    
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='color:red;'>This email is already registered.</p>";
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Hash the password before saving
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (id, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $id, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $id;
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit();
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
} else {
    echo "<p style='color:red;'>Invalid request method.</p>";
}

$conn->close();
?>
