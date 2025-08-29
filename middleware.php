<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_user() {
    if (!isset($_SESSION['user'])) {
        header("Location: auth/login.php");
        exit;
    }
}

function require_admin() {
    if (!isset($_SESSION['admin'])) {
        header("Location: auth/login.php");
        exit;
    }
}

function redirect_if_logged_in() {
    if (isset($_SESSION['user']) || isset($_SESSION['admin'])) {
        header("Location: products.php");
        exit;
    }
}
?>
