<?php
session_start();

$host = "localhost"; 
$user = "root";
$pass = "Servando_75.";
$db   = "sistema_escolar";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = mysqli_real_escape_string($conn, $_POST['matricula']);
    $password_ingresada = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE matricula = '$matricula' LIMIT 1";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $usuario = $res->fetch_assoc();

        if (password_verify($password_ingresada, $usuario['password']) || $password_ingresada == $usuario['password']) {
            if ($usuario['estatus'] == 'Inactivo') {
                $error = "Tu cuenta está desactivada.";
            } else {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['matricula'] = $usuario['matricula'];
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "La contraseña es incorrecta.";
        }
    } else {
        $error = "La matrícula no existe.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="../assets/iconos/loginIcono.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .card { border-radius: 1rem; border: none; }
    </style>
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="container-fluid px-5"> 
        <div class="row justify-content-center align-items-center">
            
            <div class="col-11 col-sm-8 col-md-4 col-lg-4">
                <div class="card shadow-lg p-5 border-0" style="border-radius: 20px;">
                    <div class="text-center mb-5">
                        <h1 class="fw-bold fs-1" style="color: #800020;">Tec San Pedro</h1>
                        <p class="text-muted">Gestión de Control Escolar</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-3 mb-4 text-center small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Matrícula</label>
                            <input type="text" name="matricula" class="form-control form-control-lg bg-light" placeholder="Ingresa tu matrícula" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Contraseña</label>
                            <input type="password" name="password" class="form-control form-control-lg bg-light" placeholder="********" required>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-lg fw-bold shadow-sm py-3" style="background-color: #800020; color: white;">
                                INGRESAR AL PORTAL
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-5">
                        <hr class="text-muted">
                        <a href="recuperar.php" class="text-decoration-none text-secondary small">
                            ¿Problemas para entrar? <strong>Recuperar acceso</strong>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-none d-md-block text-center p-5">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo Tec" class="img-fluid" style="width: 100%; height: auto;">
            </div>

        </div>
    </div>

</body>
</html>