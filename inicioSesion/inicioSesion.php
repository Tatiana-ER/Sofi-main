<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../config/database.php';

$pdo = Database::getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: ../index.php?error=campos_vacios");
        exit();
    }

    $sql = "SELECT u.*, r.nombre as rol_nombre, r.id as rol_id
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE u.username = :username";

    try {

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username
        ]);

        $usuario = $stmt->fetch();

        // Verificación segura ÚNICAMENTE con password_verify.
        // Se elimina la comparación en texto plano.
        if ($usuario && password_verify($password, $usuario['password'])) {

            // Evita fijación de sesión: se genera un ID nuevo
            // ANTES de guardar los datos del usuario en sesión.
            session_regenerate_id(true);

            $_SESSION['usuario']     = $usuario['username'];
            $_SESSION['user_id']     = $usuario['id'];
            $_SESSION['rol_id']      = $usuario['rol_id'];
            $_SESSION['rol_nombre']  = $usuario['rol_nombre'];
            $_SESSION['last_activity'] = time();

            header("Location: ../dashboard.php");
            exit();

        } else {

            header("Location: ../index.php?error=credenciales_invalidas");
            exit();

        }

    } catch (PDOException $e) {

        error_log($e->getMessage());

        header("Location: ../index.php?error=error_servidor");
        exit();

    }

} else {

    header("Location: ../index.php");
    exit();

}