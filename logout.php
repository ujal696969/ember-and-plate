<?php

session_start();

// সব session data clear
$_SESSION = [];

// Session destroy
session_destroy();

// Login page-এ পাঠিয়ে দাও
header("Location: login.php");
exit;

?>