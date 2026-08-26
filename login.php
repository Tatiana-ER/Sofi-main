<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Ingreso | SOFI UDES</title>

    <!-- Font Awesome (íconos) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Google Fonts, consistente con el resto de SOFI -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700|Poppins:400,500,600,700" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #103669;
            --primary-light: #1b4b82;
            --accent: #e8a33d;
            --bg: #f7f9fc;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-muted: #6b7684;
            --border: #dde3ec;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Open Sans', sans-serif;
        }

        html, body {
            height: 100%;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg);
        }

        .form {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ===== Panel izquierdo (marca) — logo destacado + patrón de fondo ===== */
        .leftside {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 15% 85%, rgba(232, 163, 61, 0.10) 0%, transparent 45%),
                linear-gradient(155deg, #0a2748 0%, var(--primary) 55%, var(--primary-light) 100%);
        }

        /* Patrón de "línea de crecimiento" ambiental, sutil, no protagónico */
        .growth-lines {
            position: absolute;
            inset: 0;
            opacity: 0.5;
            pointer-events: none;
        }

        .growth-lines svg {
            width: 100%;
            height: 100%;
        }

        .leftside-inner {
            position: relative;
            z-index: 2;
            width: 78%;
            max-width: 340px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 22px;
        }

        .logo-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45);
        }

        .logo-card img {
            width: 100%;
            display: block;
        }

        .leftside-caption {
            text-align: center;
            color: rgba(255, 255, 255, 0.85);
            font-size: 13.5px;
            letter-spacing: 0.3px;
            line-height: 1.6;
        }

        .leftside-caption strong {
            display: block;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            letter-spacing: 2px;
            color: var(--accent);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        /* ===== Panel derecho (formulario) ===== */
        .rightside {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background-color: var(--bg);
        }

        .input-container {
            width: 100%;
            max-width: 350px;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 44px 38px 36px 38px;
            box-shadow: 0 20px 45px -20px rgba(16, 54, 105, 0.25);
            border: 1px solid rgba(16, 54, 105, 0.06);
        }

        .rightside h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .title-underline {
            width: 46px;
            height: 4px;
            margin: 0 auto 32px auto;
            border-radius: 4px;
            background: var(--accent);
        }

        .input {
            position: relative;
            margin-bottom: 18px;
        }

        .input i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 15px;
        }

        .input input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text);
            background-color: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }

        .input input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 54, 105, 0.14);
            background-color: #fff;
        }

        .input input::placeholder {
            color: #9aa5b1;
        }

        .login {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
            margin-top: 4px;
        }

        .login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px -6px rgba(16, 54, 105, 0.55);
            filter: brightness(1.05);
        }

        .login:active {
            transform: translateY(0);
        }

        .input-container > p {
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            margin: 22px 0 10px 0;
        }

        .social-items {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 10px;
        }

        .social-items a {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f1f4f8;
            color: var(--primary);
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s, transform 0.15s;
        }

        .social-items a:hover {
            background-color: var(--primary);
            color: #fff;
            transform: translateY(-2px);
        }

        /* Botón secundario "Regístrate" */
        .input-container form:last-of-type .login {
            background: #fff;
            color: var(--primary);
            border: 1.5px solid var(--primary);
            box-shadow: none;
        }

        .input-container form:last-of-type .login:hover {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 10px 22px -6px rgba(16, 54, 105, 0.35);
        }

        @media (max-width: 860px) {
            .form {
                flex-direction: column;
            }
            .leftside {
                flex: none;
                min-height: 260px;
                padding: 30px 0;
            }
            .leftside-inner {
                max-width: 240px;
            }
            .rightside {
                padding: 30px 16px 50px;
            }
        }
    </style>
</head>
<body>

    <div class="form">
        <div class="leftside">
            <div class="growth-lines">
                <svg viewBox="0 0 600 800" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,650 90,600 180,660 270,520 360,570 450,400 540,440 600,300"
                        fill="none" stroke="rgba(255,255,255,0.14)" stroke-width="3"/>
                    <polyline points="0,750 90,710 180,740 270,630 360,660 450,540 540,570 600,470"
                        fill="none" stroke="rgba(232,163,61,0.18)" stroke-width="3"/>
                </svg>
            </div>

            <div class="leftside-inner">
                <div class="logo-card">
                    <img src="./Img/logonuevo3.jpeg" alt="Logo SOFI">
                </div>
            </div>
        </div>

        <div class="rightside">
            <div class="input-container">
                <h2>INICIO</h2>
                <div class="title-underline"></div>

                <!-- FORMULARIO DE LOGIN -->
                <form action="inicioSesion/inicioSesion.php" method="POST">
                    <div class="input">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username" required placeholder="Usuario">
                    </div>
                    <div class="input">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" required placeholder="Contraseña">
                    </div>
                    <button type="submit" class="login">INICIO</button>
                </form>

                <!-- SOCIAL MEDIA ICONS -->
                <p>Otros medios</p>
                <div class="social-items">
                    <a href="#"><i class="fa fa-facebook"></i></a>
                    <a href="#"><i class="fa fa-google"></i></a>
                    <a href="#"><i class="fa fa-twitter"></i></a>
                </div>

                <!-- FORMULARIO DE REGISTRO -->
                <p>¿No tienes cuenta?</p>
                <form action="registrarse.php" method="post">
                    <input type="submit" class="login" value="REGISTRATE">
                </form>
            </div>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>