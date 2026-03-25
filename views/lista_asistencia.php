<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Profesor', 'Administrador'])) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['horario']) || empty($_GET['horario'])) {
    die("Error: No se especificó el grupo.");
}

$id_horario = $_GET['horario'];
$conexion = new Conexion();
$db = $conexion->getConnection();

$query_info = "SELECT m.nombre_materia, m.clave_materia, g.nombre_grupo, p.nombre, p.apellido_paterno, p.apellido_materno, per.nombre_periodo 
               FROM horarios h 
               JOIN materias m ON h.id_materia = m.id_materia 
               JOIN grupos g ON h.id_grupo = g.id_grupo 
               JOIN usuarios u ON h.id_profesor = u.id_usuario 
               JOIN personas p ON u.id_usuario = p.id_usuario 
               LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
               WHERE h.id_horario = :id_horario";
$stmt_info = $db->prepare($query_info);
$stmt_info->execute([':id_horario' => $id_horario]);
$info_clase = $stmt_info->fetch(PDO::FETCH_ASSOC);

if (!$info_clase) {
    die("Error: El grupo solicitado no existe.");
}

$query_alumnos = "SELECT u.matricula, p.nombre, p.apellido_paterno, p.apellido_materno 
                  FROM carga_academica ca 
                  JOIN usuarios u ON ca.id_alumno = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  WHERE ca.id_horario = :id_horario 
                  ORDER BY p.apellido_paterno ASC, p.apellido_materno ASC, p.nombre ASC";
$stmt_alumnos = $db->prepare($query_alumnos);
$stmt_alumnos->execute([':id_horario' => $id_horario]);
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);

$dias_asistencia = 15;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Asistencia - <?php echo htmlspecialchars($info_clase['nombre_materia']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header-asistencia {
            border-bottom: 2px solid var(--rojo-vino);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .logo-tec {
            max-height: 70px;
        }

        .tabla-asistencia th,
        .tabla-asistencia td {
            border: 1px solid #000 !important;
            vertical-align: middle;
            padding: 4px 8px;
        }

        .tabla-asistencia th {
            background-color: #f0f0f0 !important;
            text-align: center;
        }

        .col-dia {
            width: 30px;
        }

        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }

            .no-imprimir {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid p-4">

        <div class="text-end mb-3 no-imprimir">
            <button onclick="window.print()" class="btn text-white fw-bold" style="background-color: #800020;">
                Imprimir Lista
            </button>
        </div>

        <div class="row header-asistencia align-items-center">
            <div class="col-3">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo Tec" class="logo-tec">
            </div>
            <div class="col-6 text-center">
                <h4 class="fw-bold mb-1" style="color: #800020; text-transform: uppercase;">Instituto Tecnológico de San Pedro</h4>
                <h6 class="fw-bold mb-0">Control de Asistencia Estudiantil</h6>
            </div>
            <div class="col-3 text-end">
                <p class="mb-0 fw-bold">Periodo:</p>
                <p class="mb-0 text-muted"><?php echo htmlspecialchars($info_clase['nombre_periodo']); ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-8">
                <p class="mb-1"><strong>Materia:</strong> <?php echo htmlspecialchars($info_clase['nombre_materia']); ?> <span class="text-muted">(<?php echo htmlspecialchars($info_clase['clave_materia']); ?>)</span></p>
                <p class="mb-0"><strong>Docente:</strong> <?php echo htmlspecialchars($info_clase['nombre'] . ' ' . $info_clase['apellido_paterno'] . ' ' . $info_clase['apellido_materno']); ?></p>
            </div>
            <div class="col-4 text-end">
                <p class="mb-1"><strong>Grupo:</strong> <?php echo htmlspecialchars($info_clase['nombre_grupo']); ?></p>
                <p class="mb-0"><strong>Total Inscritos:</strong> <?php echo count($alumnos); ?></p>
            </div>
        </div>

        <table class="table tabla-asistencia">
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 10%;">Matrícula</th>
                    <th style="width: 25%;">Nombre del Alumno</th>
                    <?php for ($i = 1; $i <= $dias_asistencia; $i++): ?>
                        <th class="col-dia"></th>
                    <?php endfor; ?>
                    <th style="width: 5%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alumnos) > 0): ?>
                    <?php $contador = 1;
                    foreach ($alumnos as $alumno): ?>
                        <tr>
                            <td class="text-center fw-bold"><?php echo $contador++; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($alumno['matricula']); ?></td>
                            <td class="fw-semibold">
                                <?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']); ?>
                            </td>
                            <?php for ($i = 1; $i <= $dias_asistencia; $i++): ?>
                                <td></td>
                            <?php endfor; ?>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $dias_asistencia + 4; ?>" class="text-center py-4">No hay alumnos inscritos en este grupo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-5 text-center">
            <p class="mb-0">___________________________________________________</p>
            <p class="fw-bold mt-1">Firma del Docente</p>
        </div>

    </div>

</body>

</html>