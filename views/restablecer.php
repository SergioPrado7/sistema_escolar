<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "nue.domcloud.co"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";
$conn = new mysqli($host, $user, $pass, $db);

$mensaje = "";
$tipo_alerta = "";
$mostrar_formulario = false;

// 1. Verificar si el token viene en la URL
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Buscar el usuario con ese token y que no haya expirado
    $sql = "SELECT * FROM usuarios WHERE reset_token = '$token' AND token_expira > NOW() LIMIT 1";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $usuario = $res->fetch_assoc();
        $mostrar_formulario = true;
    } else {
        $mensaje = "El enlace ha expirado o es inválido. Por favor, solicita uno nuevo.";
        $tipo_alerta = "alert-danger";
    }
} else {
    header("Location: login.php");
    exit();
}

// 2. Procesar el cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && $mostrar_formulario) {
    $nueva_pass = $_POST['password'];
    $confirmar_pass = $_POST['confirm_password'];

    if ($nueva_pass === $confirmar_pass) {
        $id_usuario = $usuario['id_usuario'];
        
        // Actualizar contraseña y limpiar el token para que no se use dos veces
        $update = "UPDATE usuarios SET password = '$nueva_pass', reset_token = NULL, token_expira = NULL WHERE id_usuario = $id_usuario";
        
        if ($conn->query($update)) {
            $mensaje = "¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.";
            $tipo_alerta = "alert-success";
            $mostrar_formulario = false; // Ocultamos el form tras el éxito
        } else {
            $mensaje = "Error al actualizar la contraseña.";
            $tipo_alerta = "alert-danger";
        }
    } else {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_alerta = "alert-danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Tec San Pedro</title>
    <link rel="icon" type="image/x-icon" href="../assets/iconos/loginIcono.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .card { border-radius: 20px; border: none; }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-5">
                <div class="card shadow-lg p-5 border-0">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold" style="color: #8A3324;">Nueva Contraseña</h2>
                        <p class="text-muted small">Crea una clave segura para tu cuenta.</p>
                    </div>

                    <?php if ($mensaje): ?>
                        <div class="alert <?php echo $tipo_alerta; ?> py-2 small text-center"><?php echo $mensaje; ?></div>
                    <?php endif; ?>

                    <?php if ($mostrar_formulario): ?>
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">Nueva Contraseña</label>
                                <input type="password" name="password" class="form-control form-control-lg bg-light" placeholder="********" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">Confirmar Contraseña</label>
                                <input type="password" name="confirm_password" class="form-control form-control-lg bg-light" placeholder="********" required>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-lg fw-bold shadow-sm py-3" style="background-color: #8A3324; color: white;">
                                    GUARDAR CAMBIOS
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-5">
                        <hr class="text-muted">
                        <a href="login.php" class="text-decoration-none text-secondary small"><strong>Ir al Inicio de Sesión</strong></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>