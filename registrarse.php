<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORMULARIO DE INGRESO</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    
    <div class="form">
        <div class="leftside">  
            <img src="./Img/welcome.png">
            <h1>SOFI UDES</h1>
        </div>
        <div class="rightside">
            <h2>REGISTRO</h2>
            <div class="input-container">
                <form action="InicioSesion/registrarse.php" method="post">
                    <div class="input">
                        <i class="fa fa-user"></i>
                        <input type="text" id="username" name="username" required placeholder="Usuario">
                    </div>
                    <div class="input">
                        <i class="fa fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Contraseña">
                    </div>

                    <button type="submit" class="login">REGISTRARSE</button>
                </form>
                <p>¿Ya tienes cuenta?</p>
                <form action="login.php" method="post">
                    <input type="submit" class="login" value="INICIA SESION">
                </form>

   

            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="login.js"> </script>
</body>
</html>

