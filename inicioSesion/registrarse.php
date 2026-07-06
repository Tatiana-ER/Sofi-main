<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {

        $pdo = Database::getConnection();

        // Verificar si el usuario ya existe
        $verificar = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username");
        $verificar->execute([
            ':username' => $username
        ]);

        if ($verificar->fetch()) {

            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

                <script>
                document.addEventListener('DOMContentLoaded', function () {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Usuario existente',
                        text: 'Ese nombre de usuario ya está registrado.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#f39c12'
                    }).then(() => {
                        window.location.href = '../registrarse.php';
                    });

                });
                </script>";
            exit();
        }

        // Encriptar contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Registrar usuario con rol Usuario (2)
        $sql = "INSERT INTO usuarios (username, password, rol_id)
                VALUES (:username, :password, :rol_id)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':password' => $passwordHash,
            ':rol_id'   => 2
        ]);

        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

            <script>
            document.addEventListener('DOMContentLoaded', function () {

                Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada correctamente.',
                    confirmButtonText: 'Iniciar sesión',
                    confirmButtonColor: '#3085d6',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = '../login.php';
                });

            });
            </script>";

    } catch (PDOException $e) {

        error_log($e->getMessage());

        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

            <script>
            document.addEventListener('DOMContentLoaded', function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un problema al registrar el usuario.',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#d33'
                }).then(() => {
                    window.location.href = '../registrarse.php';
                });

            });
            </script>";

    }

} else {

    header("Location: ../registrarse.php");
    exit();

}