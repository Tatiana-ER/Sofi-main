<?php
// auth_check.php — debe ir en la raíz del proyecto

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Sin sesión activa -> a la página de inicio (ahí está el botón "Iniciar Sesión")
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Cierre automático tras 30 min de inactividad
$tiempo_maximo = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tiempo_maximo) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=sesion_expirada");
    exit();
}
$_SESSION['last_activity'] = time();
?>