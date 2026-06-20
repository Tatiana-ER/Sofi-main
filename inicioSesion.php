<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config/database.php';
$pdo = Database::getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: index.php?error=campos_vacios");
        exit();
    }

    $sql = "SELECT u.*, r.nombre as rol_nombre, r.id as rol_id 
            FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            WHERE u.username = :username";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $passwordValida = false;

            if (password_verify($password, $usuario['password'])) {
                $passwordValida = true;
            } elseif ($password === $usuario['password']) {
                $passwordValida = true;
            }

            if ($passwordValida) {
                $_SESSION['usuario']    = $usuario['username'];
                $_SESSION['user_id']    = $usuario['id'];
                $_SESSION['rol_id']     = $usuario['rol_id'];
                $_SESSION['rol_nombre'] = $usuario['rol_nombre'];

                header("Location: dashboard.php");
                exit();
            } else {
                error_log("Login fallido - contraseña incorrecta: $username");
                header("Location: index.php?error=credenciales_invalidas");
                exit();
            }
        } else {
            error_log("Login fallido - usuario no existe: $username");
            header("Location: index.php?error=credenciales_invalidas");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        header("Location: index.php?error=error_servidor");
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}
?>
