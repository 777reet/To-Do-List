<?php
session_start();
session_destroy();
header("Location: index.php"); // or your login page
exit();


