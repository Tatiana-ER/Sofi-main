<?php
session_start();
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $conn = new connection();
        $pdo = $conn->connect();

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username AND password = :password");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $_SESSION['usuario'] = $username;
            header('Location: ../dashboard.php');
            exit;
        }

        // Credenciales incorrectas
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Credenciales incorrectas',
                text: 'Verifica tu usuario y contraseña'
            }).then(() => {
                window.location.href = '../login.php';
            });
        });
        </script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
