<?php
// logout.php - This script logs the user out by destroying the session and redirects to the login page.

session_start();
session_destroy();
header('Location: index.php');
exit();
?>