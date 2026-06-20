<?php
session_start();
echo "<h2>Diagnóstico de Sesión</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO ESTABLECIDO') . "\n";
echo "Todas las variables de sesión:\n";
print_r($_SESSION);
echo "</pre>";

// Probar conexión a BD
$conexion = new mysqli("localhost", "root", "", "sofi");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT u.*, r.nombre as rol_nombre FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    echo "<h3>Datos del usuario:</h3>";
    echo "<pre>";
    print_r($usuario);
    echo "</pre>";
}
?>