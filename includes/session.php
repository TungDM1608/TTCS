<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function is_uploader() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'uploader';
}

function is_reader() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'reader';
}
?>
