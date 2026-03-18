<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/Exception.php';
require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = "nue.domcloud.co"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";
$conn = new mysqli($host, $user, $pass, $db);

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = mysqli_real_escape_string($conn, $_POST['correo']);
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo' LIMIT 1";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $token = bin2hex(random_bytes(20));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // Actualizar token en la base de datos
        $conn->query("UPDATE usuarios SET reset_token='$token', token_expira='$expira' WHERE correo='$correo'");

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sergio.prado.22isc@tecsanpedro.edu.mx';
            $mail->Password   = 'dkpf canj dctf ojis'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom('sergio.prado.22isc@tecsanpedro.edu.mx', 'Tec San Pedro');
            $mail->addAddress($correo);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperar Contraseña - Tec San Pedro';
            $mail->Body    = "
                <html>
                <body style='font-family: sans-serif;'>
                    <h2 style='color: #8A3324;'>Recuperación de Acceso</h2>
                    <p>Has solicitado restablecer tu contraseña para el sistema de Control Escolar.</p>
                    <p>Haz clic en el siguiente botón para continuar:</p>
                    <a href='https://sitiosergio.nue.dom.my.id/sistema_escolar/views/restablecer.php?token=$token' 
                       style='background-color: #8A3324; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                       Restablecer Contraseña
                    </a>
                    <p style='margin-top: 20px; font-size: 0.8em; color: #666;'>Este enlace expirará en 1 hora.</p>
                </body>
                </html>";

            // ENVIAR CORREO (Sin imprimir nada en pantalla)
            $mail->send();
            
            $mensaje = "Correo enviado con éxito. Revisa tu bandeja de entrada.";
            $tipo_alerta = "alert-success";

        } catch (Exception $e) {
            $mensaje = "Error al enviar: {$mail->ErrorInfo}";
            $tipo_alerta = "alert-danger";
        }
    } else {
        $mensaje = "El correo ingresado no está registrado.";
        $tipo_alerta = "alert-danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
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
                        <h2 class="fw-bold" style="color: #8A3324;">Recuperar Acceso</h2>
                        <p class="text-muted small">Usa tu correo institucional para recibir las instrucciones.</p>
                    </div>

                    <?php if ($mensaje): ?>
                        <div class="alert <?php echo $tipo_alerta; ?> py-2 small text-center"><?php echo $mensaje; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Correo Institucional</label>
                            <input type="email" name="correo" class="form-control form-control-lg bg-light" placeholder="usuario@tecsanpedro.edu.mx" required>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-lg fw-bold shadow-sm py-3" style="background-color: #8A3324; color: white;">
                                ENVIAR ENLACE
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-5">
                        <hr class="text-muted">
                        <a href="login.php" class="text-decoration-none text-secondary small">
                            <strong>< Volver al Login</strong>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>