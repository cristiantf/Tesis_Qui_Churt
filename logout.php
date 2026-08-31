<?php
/**
 * Logout — Cerrar sesión
 */
require_once __DIR__ . '/config/database.php';

session_start();
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
