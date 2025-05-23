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

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $stmt->close();
            $conn->close();
            header("Location: index.php");
            exit();
        } else {
            echo "<p style='color:red;'>Invalid ID or password.</p>";
        }
    } else {
        echo "<p style='color:red;'>Invalid ID or password.</p>";
    }

    $stmt->close();
} else {
    echo "<p style='color:red;'>Invalid request method.</p>";
}

$conn->close();
?>
