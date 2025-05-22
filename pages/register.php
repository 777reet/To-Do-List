<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "todolist";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id = htmlspecialchars(trim($_POST['id']));
    $password = htmlspecialchars(trim($_POST['password']));

    $errors = [];

    // Validate email for id (since id is now VARCHAR email)
    if (empty($id) || !filter_var($id, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required for ID.";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
        exit();
    }

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO users (id, password) VALUES (?, ?)");

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $id, $password);

    if ($stmt->execute()) {
        // Set session user_id for auto-login
        $_SESSION['user_id'] = $id;

        $stmt->close();
        $conn->close();

        header("Location: http://localhost/todolist/index.php");
        exit();
    } else {
        echo "<p style='color:red;'>❌ Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p style='color:red;'>Invalid request method.</p>";
}
?>
