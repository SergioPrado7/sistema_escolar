<?php
session_start();

// Validar que sea un alumno y que traiga el ID del servicio
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Alumno' || !isset($_GET['id'])) {
    die("Acceso denegado.");
}

$id_servicio = $_GET['id'];
$id_alumno = $_SESSION['id_usuario'];

// Conexión a la base de datos
$host = "localhost"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";

$conexion = new mysqli($host, $user, $pass, $db);
$conexion->set_charset("utf8mb4");

// Consultamos los detalles cruzando información con usuario, persona y carrera
$sql = "SELECT s.*, u.matricula, p.nombre, p.apellido_paterno, c.nombre_carrera 
        FROM servicio_social s
        INNER JOIN usuarios u ON s.id_alumno = u.id_usuario
        INNER JOIN personas p ON u.id_usuario = p.id_usuario
        LEFT JOIN alumnos_detalles ad ON u.id_usuario = ad.id_alumno
        LEFT JOIN carreras c ON ad.id_carrera = c.id_carrera
        WHERE s.id_servicio = ? AND s.id_alumno = ? AND s.estatus = 'Liberado'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_servicio, $id_alumno);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Documento no disponible. Tu servicio social aún no está liberado.</h2>");
}

$datos = $resultado->fetch_assoc();
$nombre_completo = $datos['nombre'] . ' ' . $datos['apellido_paterno'];
$carrera = $datos['nombre_carrera'] ?? 'Carrera No Especificada';

// Formateo de fechas para que se vean elegantes
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
$fecha_inicio_formato = strftime("%d de %B de %Y", strtotime($datos['fecha_inicio']));
$fecha_termino_formato = strftime("%d de %B de %Y", strtotime($datos['fecha_termino']));
$fecha_hoy = strftime("%d de %B de %Y");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Servicio Social - <?php echo $datos['matricula']; ?></title>
    <style>
        body { 
            font-family: 'Georgia', serif; 
            background-color: #525659; 
            margin: 0; 
            padding: 0; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .hoja-carta { 
            background: #fff; 
            width: 21.59cm; 
            height: 27.94cm; 
            padding: 2.5cm; 
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative;
            margin-bottom: 40px;
        }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #800020; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #800020; font-size: 26px; text-transform: uppercase; }
        .header h2 { margin: 5px 0 0; color: #333; font-size: 18px; font-weight: normal; }
        .asunto { text-align: right; margin-bottom: 40px; font-weight: bold; }
        .titulo-doc { text-align: center; font-size: 22px; font-weight: bold; margin-bottom: 40px; letter-spacing: 2px; }
        .cuerpo-texto { text-align: justify; line-height: 2; font-size: 16px; margin-bottom: 50px; }
        .firma-caja { text-align: center; margin-top: 80px; }
        .linea-firma { width: 300px; border-top: 1px solid #000; margin: 0 auto 10px auto; }
        .firma-texto { font-weight: bold; margin: 0; }
        .firma-puesto { font-size: 14px; color: #555; margin: 0; }
        .footer-doc { position: absolute; bottom: 2cm; left: 2.5cm; right: 2.5cm; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
        
        /* Ocultar elementos al imprimir */
        @media print {
            body { background-color: #fff; padding: 0; display: block; }
            .hoja-carta { width: auto; height: auto; box-shadow: none; padding: 0; margin: 0; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin: 20px 0;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #800020; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-family: sans-serif; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
            Imprimir
        </button>
    </div>

    <div class="hoja-carta">
        <div class="header">
            <h1>Instituto Tecnológico de San Pedro</h1>
            <h2>Departamento de Gestión Tecnológica y Vinculación</h2>
        </div>

        <div class="asunto">
            Asunto: Constancia de Terminación de Servicio Social.<br>
            San Pedro, Coahuila a <?php echo $fecha_hoy; ?>.
        </div>

        <div class="titulo-doc">
            A QUIEN CORRESPONDA:
        </div>

        <div class="cuerpo-texto">
            Por medio de la presente, el Departamento de Gestión Tecnológica y Vinculación hace constar que el (la) alumno(a) <strong><?php echo mb_strtoupper($nombre_completo); ?></strong>, con número de control <strong><?php echo $datos['matricula']; ?></strong>, perteneciente a la carrera de <strong><?php echo mb_strtoupper($carrera); ?></strong>, ha concluido satisfactoriamente su Servicio Social reglamentario.
            <br><br>
            Dicho servicio fue prestado en la dependencia <strong>"<?php echo mb_strtoupper($datos['dependencia']); ?>"</strong>, durante el periodo comprendido del <strong><?php echo $fecha_inicio_formato; ?></strong> al <strong><?php echo $fecha_termino_formato; ?></strong>, cubriendo un total de <strong>480 horas</strong>, mostrando en todo momento responsabilidad, dedicación y profesionalismo.
            <br><br>
            A petición del interesado(a) y para los fines legales que a este(a) convengan, se extiende la presente constancia.
        </div>

        <div class="firma-caja">
            <div class="linea-firma"></div>
            <p class="firma-texto">ING. ADMINISTRADOR DE SISTEMA</p>
            <p class="firma-puesto">Jefe del Depto. de Gestión y Vinculación</p>
        </div>

        <div class="footer-doc">
            Documento generado a través del Gestor Escolar Tec San Pedro.<br>
            Esta constancia es válida únicamente con firma y sello original del departamento correspondiente.
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>