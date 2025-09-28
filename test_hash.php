<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    echo "<h3>Contraseña original:</h3> " . $password;
    echo "<h3>Hash generado:</h3> " . $hash;

    echo "<h3>¿Quieres verificarla? Escribe la contraseña original:</h3>
        <form method='post'>
            <input type='hidden' name='hash' value='$hash'>
            <input type='text' name='verify_password' placeholder='Contraseña a verificar'>
            <button type='submit'>Verificar</button>
        </form>";

    if (isset($_POST['verify_password'])) {
        $verify = $_POST['verify_password'];
        $valid = password_verify($verify, $_POST['hash']);
        echo $valid ? "<p style='color:green;'>✔ Contraseña correcta</p>" : "<p style='color:red;'>✘ Contraseña incorrecta</p>";
    }
    exit;
}
?>

<h2>Probar hash y verificación</h2>
<form method="post">
    <input type="text" name="password" placeholder="Escribe una contraseña">
    <button type="submit">Generar Hash</button>
</form>