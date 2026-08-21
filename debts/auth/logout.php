<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['site_user_id']);
session_destroy();
header('Location: ../index.php');
exit;
