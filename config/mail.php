<?php
// ============================================================
//  config/mail.php
//  Envío de correos (recuperar contraseña).
//
//  MODO DESARROLLO (por defecto): no envía nada; guarda el correo
//  con el enlace en  storage/correos.log  para que puedas probar
//  en XAMPP sin configurar SMTP.
//
//  PARA ENVIAR CORREOS REALES: pon 'enviar_real' => true y llena
//  los datos SMTP (ejemplo con Gmail: crea una "contraseña de
//  aplicación" en tu cuenta de Google, NO uses tu clave normal).
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

const MAIL_CONFIG = [
    'enviar_real' => false,                 // true = enviar por SMTP
    'host'        => 'smtp.gmail.com',
    'puerto'      => 587,
    'usuario'     => 'tu_correo@gmail.com',
    'clave'       => 'CLAVE_DE_APLICACION',
    'seguridad'   => 'tls',                 // 'tls' (587) o 'ssl' (465)
    'from_email'  => 'tu_correo@gmail.com',
    'from_nombre' => 'SOFI UDES',
    // URL base de SOFI para armar el enlace del correo, sin barra final.
    // Ej: 'http://localhost/Sofi-main'  o  'https://midominio.com/sofi'
    // Si la dejas vacía se deduce del servidor.
    'base_url'    => '',
];

function enviarCorreo(string $para, string $asunto, string $htmlCuerpo): bool
{
    $cfg = MAIL_CONFIG;

    if (!$cfg['enviar_real']) {
        $linea = "[" . date('Y-m-d H:i:s') . "] PARA: $para | ASUNTO: $asunto\n"
               . strip_tags(str_replace(['<br>', '</p>'], "\n", $htmlCuerpo)) . "\n"
               . str_repeat('-', 60) . "\n";
        @file_put_contents(__DIR__ . '/../storage/correos.log', $linea, FILE_APPEND);
        return true;
    }

    try {
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host       = $cfg['host'];
        $m->SMTPAuth   = true;
        $m->Username   = $cfg['usuario'];
        $m->Password   = $cfg['clave'];
        $m->SMTPSecure = $cfg['seguridad'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port       = $cfg['puerto'];
        $m->CharSet    = 'UTF-8';
        $m->setFrom($cfg['from_email'], $cfg['from_nombre']);
        $m->addAddress($para);
        $m->isHTML(true);
        $m->Subject = $asunto;
        $m->Body    = $htmlCuerpo;
        $m->AltBody = strip_tags($htmlCuerpo);
        $m->send();
        return true;
    } catch (MailException $e) {
        error_log('Error enviando correo: ' . $e->getMessage());
        return false;
    }
}
