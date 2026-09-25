<?php
session_start();

$_SESSION = [];
session_destroy();

// config file include karo jahan base_url defined hai
include "config/config.php"; // apne project ke hisaab se path change kar

header("Location: " . $base_url . "login.php");
exit;
?>