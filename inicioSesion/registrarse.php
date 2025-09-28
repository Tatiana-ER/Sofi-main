<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // SIN HASH

    try {
        $connection = new connection();
        $pdo = $connection->connect();

        $sql = "INSERT INTO usuarios(username, password) VALUES (:username, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'password' => $password,
        ]);

        echo "<script>
            alert('Usuario registrado correctamente.');
            window.location.href = '../dashboard.php';
        </script>";

    } catch (Exception $e) {
        echo "<script>
            alert('Error al registrar el usuario: " . addslashes($e->getMessage()) . "');
            window.location.href = '../registrarse.php';
        </script>";
    }
}
?>