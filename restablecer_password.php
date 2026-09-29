<?php
// restablecer_password.php — paso 2: el usuario llega desde el correo y crea su nueva contraseña
session_start();
require_once __DIR__ . '/config/database.php';

$pdo   = Database::getConnection();
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$valido = false;
$error  = null;
$usuarioId = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = $pdo->prepare("SELECT id, usuario_id FROM password_resets
                         WHERE token_hash = :h AND usado = 0 AND expira_en > NOW() LIMIT 1");
    $st->execute([':h' => hash('sha256', $token)]);
    $reset = $st->fetch();
    if ($reset) { $valido = true; $usuarioId = $reset['usuario_id']; }
}

if ($valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id")
                ->execute([':p' => password_hash($pass, PASSWORD_DEFAULT), ':id' => $usuarioId]);
            // El token (y cualquier otro pendiente) queda inutilizable
            $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = :id")
                ->execute([':id' => $usuarioId]);
            $pdo->commit();
            header('Location: login.php?reset=ok');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('restablecer_password: ' . $e->getMessage());
            $error = 'No se pudo actualizar la contraseña. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | SOFI UDES</title>
    <link href="assets/img/favicon.png" rel="icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700|Poppins:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="form">
        <div class="leftside">
            <div class="growth-lines">
                <svg viewBox="0 0 600 800" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,650 90,600 180,660 270,520 360,570 450,400 540,440 600,300" fill="none" stroke="rgba(255,255,255,0.14)" stroke-width="3"/>
                    <polyline points="0,750 90,710 180,740 270,630 360,660 450,540 540,570 600,470" fill="none" stroke="rgba(232,163,61,0.18)" stroke-width="3"/>
                </svg>
            </div>
            <div class="leftside-inner">
                <div class="logo-card"><img src="./Img/logonuevo3.jpeg" alt="Logo SOFI"></div>
            </div>
        </div>
        <div class="rightside">
            <div class="input-container">
<?php if ($valido): ?>
                <h2>NUEVA CLAVE</h2>
                <div class="title-underline"></div>
                <p class="auth-help">Escribe tu nueva contraseña (mínimo 8 caracteres).</p>

                <?php if ($error): ?>
                    <p class="auth-help" style="color:#c0392b;font-weight:600;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form action="restablecer_password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="input">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" class="has-toggle" required minlength="8" placeholder="Nueva contraseña">
                        <i class="fa fa-eye toggle-pass"></i>
                    </div>
                    <div class="input">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password2" class="has-toggle" required minlength="8" placeholder="Repite la contraseña">
                        <i class="fa fa-eye toggle-pass"></i>
                    </div>
                    <button type="submit" class="login">GUARDAR CONTRASEÑA</button>
                </form>
<?php else: ?>
                <h2>ENLACE INVÁLIDO</h2>
                <div class="title-underline"></div>
                <p class="auth-help">Este enlace no es válido, ya fue usado o venció. Solicita uno nuevo.</p>
                <a href="olvide_password.php" class="login" style="display:block;text-align:center;text-decoration:none;">SOLICITAR NUEVO ENLACE</a>
<?php endif; ?>

                <div class="back-link"><a href="login.php"><i class="fa fa-arrow-left"></i> Volver a iniciar sesión</a></div>
            </div>
        </div>
    </div>
<script>
document.querySelectorAll('.toggle-pass').forEach(function (ic) {
    ic.addEventListener('click', function () {
        var inp = ic.parentElement.querySelector('input');
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        ic.classList.toggle('fa-eye', !show);
        ic.classList.toggle('fa-eye-slash', show);
    });
});
</script>
</body>
</html>
