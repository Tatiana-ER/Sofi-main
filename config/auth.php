<?php
// ============================================================
//  config/auth.php
//  Verificar que el usuario esté logueado.
//  Uso: require_once __DIR__ . '/../config/auth.php';   (desde views/)
//       require_once __DIR__ . '/config/auth.php';       (desde raíz)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    // Redirigir al login según desde dónde se llame
    $profundidad = substr_count($_SERVER['SCRIPT_NAME'], '/') - 1;
    $ruta = str_repeat('../', max(0, $profundidad - 1)) . 'index.php';
    header('Location: ' . $ruta);
    exit;
}
