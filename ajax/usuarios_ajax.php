<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';

// Solo el administrador puede usar este endpoint
if (($_SESSION['rol_id'] ?? null) != 1) {
    http_response_code(403);
    die('No autorizado');
}

$pdo = Database::getConnection();
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    case 'crear_usuario':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rolId    = (int)($_POST['rol_id'] ?? 2);
        $email    = trim($_POST['email'] ?? '');

        if ($username === '' || $password === '') { echo 'Datos incompletos'; exit; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo 'Correo no válido'; exit; }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password, rol_id) VALUES (:u, :e, :p, :r)");
            $stmt->execute([':u' => $username, ':e' => ($email !== '' ? $email : null), ':p' => $hash, ':r' => $rolId]);
            echo 'ok';
        } catch (PDOException $e) {
            echo 'El usuario o el correo ya existe';
        }
        break;

    case 'cambiar_rol':
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        $rolId     = (int)($_POST['rol_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE usuarios SET rol_id = :r WHERE id = :id");
        $stmt->execute([':r' => $rolId, ':id' => $usuarioId]);
        echo 'ok';
        break;

    case 'crear_rol':
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') { echo 'Nombre vacío'; exit; }

        $pdo->prepare("INSERT INTO roles (nombre) VALUES (:n)")->execute([':n' => $nombre]);
        $rolId = $pdo->lastInsertId();

        // Por defecto el rol nuevo no ve ni edita nada hasta que el admin lo configure
        $modulos = $pdo->query("SELECT id FROM modulos")->fetchAll();
        $stmt = $pdo->prepare("INSERT INTO permisos_rol (rol_id, modulo_id, puede_ver, puede_editar) VALUES (:r, :m, 0, 0)");
        foreach ($modulos as $m) {
            $stmt->execute([':r' => $rolId, ':m' => $m['id']]);
        }
        echo 'ok';
        break;

    case 'editar_usuario':
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $email     = trim($_POST['email'] ?? '');

        if ($usuarioId === 0 || $username === '') { echo 'Datos incompletos'; exit; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo 'Correo no válido'; exit; }
        $emailDb = ($email !== '' ? $email : null);

        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username = :u, email = :e, password = :p WHERE id = :id");
                $stmt->execute([':u' => $username, ':e' => $emailDb, ':p' => $hash, ':id' => $usuarioId]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET username = :u, email = :e WHERE id = :id");
                $stmt->execute([':u' => $username, ':e' => $emailDb, ':id' => $usuarioId]);
            }
            echo 'ok';
        } catch (PDOException $e) {
            echo 'Ese usuario o correo ya existe';
        }
        break;

    case 'eliminar_usuario':
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);

        // No permitir que el admin se elimine a sí mismo
        if ($usuarioId === (int)($_SESSION['user_id'] ?? 0)) {
            echo 'No puedes eliminar tu propio usuario';
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $usuarioId]);
        echo 'ok';
        break;

    case 'guardar_permiso':
        $rolId    = (int)($_POST['rol_id'] ?? 0);
        $moduloId = (int)($_POST['modulo_id'] ?? 0);
        $campo    = $_POST['campo'] === 'editar' ? 'puede_editar' : 'puede_ver';
        $valor    = (int)($_POST['valor'] ?? 0);

        // No permitir tocar el rol administrador (id 1)
        if ($rolId == 1) { echo 'No permitido'; exit; }

        $sql = "UPDATE permisos_rol SET $campo = :v WHERE rol_id = :r AND modulo_id = :m";
        $pdo->prepare($sql)->execute([':v' => $valor, ':r' => $rolId, ':m' => $moduloId]);
        echo 'ok';
        break;

    default:
        echo 'Acción no reconocida';
}