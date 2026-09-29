<?php
// olvide_password.php — paso 1: el usuario pide el enlace de recuperación
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail.php';

$mensaje = null; // se muestra con SweetAlert tras el envío

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = ['icon' => 'warning', 'title' => 'Correo no válido', 'text' => 'Escribe una dirección de correo válida.'];
    } else {
        try {
            $pdo = Database::getConnection();

            $st = $pdo->prepare("SELECT id, username FROM usuarios WHERE email = :e LIMIT 1");
            $st->execute([':e' => $email]);
            $u = $st->fetch();

            if ($u) {
                // Anti-abuso: máximo 1 solicitud cada 2 minutos por usuario
                $rec = $pdo->prepare("SELECT COUNT(*) FROM password_resets
                                      WHERE usuario_id = :id AND creado_en > (NOW() - INTERVAL 2 MINUTE)");
                $rec->execute([':id' => $u['id']]);

                if ((int)$rec->fetchColumn() === 0) {
                    // Invalida solicitudes anteriores
                    $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = :id AND usado = 0")
                        ->execute([':id' => $u['id']]);

                    $token = bin2hex(random_bytes(32));               // va en el enlace
                    $hash  = hash('sha256', $token);                   // en la BD solo el hash

                    $pdo->prepare("INSERT INTO password_resets (usuario_id, token_hash, expira_en)
                                   VALUES (:id, :h, NOW() + INTERVAL 1 HOUR)")
                        ->execute([':id' => $u['id'], ':h' => $hash]);

                    $base = rtrim(MAIL_CONFIG['base_url'] ?: (
                        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                        . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')
                    ), '/');
                    $enlace = $base . '/restablecer_password.php?token=' . $token;
                    $usuario = htmlspecialchars($u['username']);

                    enviarCorreo($email, 'Recuperar contraseña - SOFI',
                        "<p>Hola <strong>$usuario</strong>,</p>"
                        . "<p>Recibimos una solicitud para restablecer tu contraseña de SOFI.</p>"
                        . "<p><a href=\"$enlace\">Haz clic aquí para crear una nueva contraseña</a></p>"
                        . "<p>Si el botón no funciona, copia este enlace en tu navegador:<br>$enlace</p>"
                        . "<p>El enlace vence en 1 hora y solo se puede usar una vez. "
                        . "Si no fuiste tú, ignora este mensaje.</p>");
                }
            }
        } catch (Throwable $e) {
            error_log('olvide_password: ' . $e->getMessage());
        }

        // Respuesta idéntica exista o no el correo (evita revelar qué correos están registrados)
        $mensaje = ['icon' => 'success', 'title' => 'Revisa tu correo',
                    'text' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa también la carpeta de spam.'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | SOFI UDES</title>
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
                <h2>RECUPERAR</h2>
                <div class="title-underline"></div>
                <p class="auth-help">Escribe el correo asociado a tu cuenta y te enviaremos un enlace para crear una nueva contraseña.</p>

                <form action="olvide_password.php" method="POST">
                    <div class="input">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Correo electrónico"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="login">ENVIAR ENLACE</button>
                </form>

                <div class="back-link"><a href="login.php"><i class="fa fa-arrow-left"></i> Volver a iniciar sesión</a></div>
            </div>
        </div>
    </div>
<?php if ($mensaje): ?>
<script>
Swal.fire({
    icon: <?= json_encode($mensaje['icon']) ?>,
    title: <?= json_encode($mensaje['title']) ?>,
    text: <?= json_encode($mensaje['text']) ?>,
    confirmButtonColor: '#103669'
});
</script>
<?php endif; ?>
</body>
</html>
