<?php
session_start();

// Vacía todas las variables de sesión
$_SESSION = array();

// Borra la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruye la sesión en el servidor
session_destroy();

// Evita que el navegador muestre una versión en caché tras cerrar sesión
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirige al MISMO login que usa el resto del sistema
header('Location: ../index.php');
exit;