<?php
session_start();
session_destroy();
header("Location: login.html");
exit();

// session out page done 

