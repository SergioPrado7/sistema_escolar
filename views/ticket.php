<?php
session_start();

// Validar que sea un alumno y que venga un ID de cargo en la URL
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Alumno' || !isset($_GET['id'])) {
    die("Acceso denegado o cargo no especificado.");
}

$id_cargo = $_GET['id'];
$id_alumno = $_SESSION['id_usuario'];

// Conexión a la base de datos
$host = "localhost"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";

$conexion = new mysqli($host, $user, $pass, $db);
$conexion->set_charset("utf8mb4");

// Consultamos los detalles del cargo cruzando información con el usuario y la persona
$sql = "SELECT c.*, u.matricula, p.nombre, p.apellido_paterno, p.apellido_materno 
        FROM cargos_financieros c
        INNER JOIN usuarios u ON c.id_alumno = u.id_usuario
        INNER JOIN personas p ON u.id_usuario = p.id_usuario
        WHERE c.id_cargo = ? AND c.id_alumno = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_cargo, $id_alumno);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Cargo no encontrado o no tienes permiso para verlo.");
}

$cargo = $resultado->fetch_assoc();
$nombre_completo = $cargo['nombre'] . ' ' . $cargo['apellido_paterno'] . ' ' . $cargo['apellido_materno'];
// Generamos una referencia bancaria ficticia basada en el cargo y matrícula
$referencia = "REF" . date("Y") . "-" . $cargo['matricula'] . "-" . str_pad($cargo['id_cargo'], 4, "0", STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Pago - <?php echo $cargo['concepto']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .ticket-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-top: 6px solid #800000; /* Color vino */ }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #333; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .detalles { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .detalles th, .detalles td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        .detalles th { color: #555; width: 40%; }
        .total-box { background-color: #f9f9f9; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
        .total-box h2 { margin: 0; color: #d9534f; font-size: 32px; }
        .barcode { text-align: center; margin-top: 20px; font-family: 'Courier New', Courier, monospace; letter-spacing: 2px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
        
        /* Ocultar botones al imprimir */
        @media print {
            body { background-color: #fff; padding: 0; }
            .ticket-container { box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #800000; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Imprimir / Guardar como PDF</button>
    </div>

    <div class="ticket-container">
        <div class="header">
            <h1>Gestor Tec San Pedro</h1>
            <p>Ficha de Pago Referenciado</p>
        </div>

        <table class="detalles">
            <tr>
                <th>Alumno:</th>
                <td><?php echo mb_strtoupper($nombre_completo); ?></td>
            </tr>
            <tr>
                <th>Matrícula:</th>
                <td><?php echo $cargo['matricula']; ?></td>
            </tr>
            <tr>
                <th>Concepto:</th>
                <td><strong><?php echo mb_strtoupper($cargo['concepto']); ?></strong></td>
            </tr>
            <tr>
                <th>Fecha Límite de Pago:</th>
                <td><?php echo date("d/m/Y", strtotime($cargo['fecha_limite'])); ?></td>
            </tr>
            <tr>
                <th>Referencia Bancaria:</th>
                <td><strong><?php echo $referencia; ?></strong></td>
            </tr>
        </table>

        <div class="total-box">
            <p style="margin: 0 0 5px 0; color: #666;">Total a Pagar</p>
            <h2>$<?php echo number_format($cargo['monto'], 2); ?> MXN</h2>
        </div>

        <div class="barcode">
            <p style="margin-bottom: 5px; font-size: 14px;">|| |||| | || | |||| || || | |||</p>
            <span><?php echo $referencia; ?></span>
        </div>

        <div class="footer">
            <p>Este documento es válido para realizar su pago en ventanillas bancarias autorizadas.</p>
            <p>Fecha de emisión: <?php echo date("d/m/Y H:i"); ?></p>
        </div>
    </div>

    <script>
        // Abre automáticamente la ventana de impresión al cargar la página
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>