<?php
// Mantén tu lógica PHP igual arriba...
session_start();
$host = "nue.domcloud.co"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = mysqli_real_escape_string($conn, $_POST['matricula']);
    $password_ingresada = $_POST['password'];
    $sql = "SELECT * FROM usuarios WHERE matricula = '$matricula' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $usuario = $res->fetch_assoc();
        if (password_verify($password_ingresada, $usuario['password']) || $password_ingresada == $usuario['password']) {
            if ($usuario['estatus'] == 'Inactivo') { $error = "Tu cuenta está desactivada."; } 
            else {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['rol'] = $usuario['rol'];
                header("Location: dashboard.php");
                exit();
            }
        } else { $error = "La contraseña es incorrecta."; }
    } else { $error = "La matrícula no existe."; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tec San Pedro</title>
    <link rel="icon" type="image/x-icon" href="../assets/iconos/loginIcono.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f7f6; 
            min-height: 100vh; 
        }
        /* Limita el ancho en monitores muy grandes para que no se vea desproporcionado */
        .login-wrapper {
            max-width: 1200px;
            width: 100%;
        }
        .card { 
            border-radius: 1.5rem; 
            border: none; 
            transition: transform 0.3s ease;
        }
        /* Ajuste de fuentes para móviles */
        @media (max-width: 576px) {
            h1 { font-size: 1.8rem !important; }
            .card { padding: 1.5rem !important; }
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center p-3">

    <div class="login-wrapper"> 
        <div class="row justify-content-center align-items-center g-4">
            
            <div class="col-12 col-md-6 col-lg-5 col-xl-5">
                <div class="card shadow-lg p-4 p-md-5 bg-white">
                    <div class="text-center mb-4 mb-md-5">
                        <h1 class="fw-bold display-6" style="color: #800020;">Tec San Pedro</h1>
                        <p class="text-muted">Gestión de Control Escolar</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 mb-4 text-center small shadow-sm"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3 mb-md-4">
                            <label class="form-label fw-bold text-secondary">Matrícula</label>
                            <input type="text" name="matricula" class="form-control form-control-lg bg-light border-0" placeholder="Ingresa tu matrícula" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Contraseña</label>
                            <input type="password" name="password" class="form-control form-control-lg bg-light border-0" placeholder="********" required>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-lg fw-bold shadow-sm py-3 text-white" style="background-color: #800020;">
                                INGRESAR AL PORTAL
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4 mt-md-5">
                        <hr class="text-muted opacity-25">
                        <a href="recuperar.php" class="text-decoration-none text-secondary small">
                            ¿Problemas para entrar? <strong>Recuperar acceso</strong>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5 col-xl-5 d-none d-md-block text-center">
                <div class="p-4">
                    <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo Tec" class="img-fluid" style="max-height: 400px; width: auto;">
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>