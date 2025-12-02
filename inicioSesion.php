<?php
session_start();

// Evitar que se guarde en caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "sofi");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtener y sanitizar datos del formulario
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validar que los campos no estén vacíos
    if (empty($username) || empty($password)) {
        header("Location: index.php?error=campos_vacios");  // SIN ../
        exit();
    }
    
    // Preparar consulta para obtener usuario con su rol
    $sql = "SELECT u.*, r.nombre as rol_nombre, r.id as rol_id 
            FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            WHERE u.username = ?";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        header("Location: index.php?error=error_servidor");  // SIN ../
        exit();
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verificar si el usuario existe
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar la contraseña
        $passwordValida = false;
        
        // Opción 1: Si usas password_hash() (RECOMENDADO)
        if (password_verify($password, $usuario['password'])) {
            $passwordValida = true;
        }
        // Opción 2: Si guardas en texto plano (TEMPORAL, cambiar después)
        elseif ($password === $usuario['password']) {
            $passwordValida = true;
        }
        
        if ($passwordValida) {
            // Credenciales correctas - Crear sesión
            $_SESSION['usuario'] = $usuario['username'];
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['rol_id'] = $usuario['rol_id'];
            $_SESSION['rol_nombre'] = $usuario['rol_nombre'];
            
            // Log para debugging (REMOVER en producción)
            error_log("Login exitoso - Usuario: " . $usuario['username'] . ", Rol: " . $usuario['rol_nombre']);
            
            // Redirigir al dashboard
            header("Location: dashboard.php");  // SIN ../
            exit();
            
        } else {
            // Contraseña incorrecta
            error_log("Intento de login fallido - Usuario: $username - Contraseña incorrecta");
            header("Location: index.php?error=credenciales_invalidas");  // SIN ../
            exit();
        }
        
    } else {
        // Usuario no encontrado
        error_log("Intento de login fallido - Usuario: $username - No existe");
        header("Location: index.php?error=credenciales_invalidas");  // SIN ../
        exit();
    }
    
    $stmt->close();
    
} else {
    // Si intentan acceder directamente sin POST
    header("Location: index.php");  // SIN ../
    exit();
}

$conexion->close();
?>
