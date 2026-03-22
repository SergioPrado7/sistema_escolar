<?php
session_start();

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Profesor', 'Administrador']) || !isset($_GET['horario'])) {
    die("Acceso denegado.");
}

require_once '../config/database.php';
$id_horario = $_GET['horario'];
$conexion = new Conexion();
$db = $conexion->getConnection();

// Datos del Grupo
$q_grupo = "SELECT h.*, m.nombre_materia, g.nombre_grupo, p.nombre, p.apellido_paterno, per.nombre_periodo 
            FROM horarios h INNER JOIN materias m ON h.id_materia = m.id_materia INNER JOIN grupos g ON h.id_grupo = g.id_grupo 
            INNER JOIN usuarios u ON h.id_profesor = u.id_usuario INNER JOIN personas p ON u.id_usuario = p.id_usuario 
            LEFT JOIN periodos per ON h.id_periodo = per.id_periodo WHERE h.id_horario = ?";
$stmt_grupo = $db->prepare($q_grupo);
$stmt_grupo->execute([$id_horario]);
$info_grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if(!$info_grupo) die("Grupo no encontrado.");

// Datos de los Alumnos
$q_alumnos = "SELECT ca.*, u.matricula, p.nombre, p.apellido_paterno 
              FROM carga_academica ca INNER JOIN usuarios u ON ca.id_alumno = u.id_usuario INNER JOIN personas p ON u.id_usuario = p.id_usuario 
              WHERE ca.id_horario = ? ORDER BY p.apellido_paterno ASC";
$stmt_alumnos = $db->prepare($q_alumnos);
$stmt_alumnos->execute([$id_horario]);
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Calificaciones</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        .hoja { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
        .header { text-align: center; border-bottom: 2px solid #800020; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #800020; font-size: 22px; }
        .info-tabla { width: 100%; margin-bottom: 30px; font-size: 14px; }
        .info-tabla td { padding: 5px; }
        .calif-tabla { width: 100%; border-collapse: collapse; font-size: 13px; text-align: center; }
        .calif-tabla th, .calif-tabla td { border: 1px solid #ccc; padding: 8px; }
        .calif-tabla th { background-color: #f8f9fa; }
        .firmas { margin-top: 80px; text-align: center; }
        .linea-firma { width: 300px; border-top: 1px solid #000; margin: 0 auto 5px auto; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #800020; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        @media print { body { background: #fff; padding: 0; } .hoja { box-shadow: none; max-width: 100%; padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button class="btn-print" onclick="window.print()">Imprimir Acta Oficial</button></div>
    <div class="hoja">
        <div class="header">
            <h1>Instituto Tecnológico de San Pedro</h1>
            <p style="margin: 5px 0 0 0; color: #555;">Acta Oficial de Calificaciones</p>
        </div>
        <table class="info-tabla">
            <tr><td><strong>Materia:</strong> <?php echo htmlspecialchars($info_grupo['nombre_materia']); ?></td><td><strong>Grupo:</strong> <?php echo htmlspecialchars($info_grupo['nombre_grupo']); ?></td></tr>
            <tr><td><strong>Profesor:</strong> <?php echo htmlspecialchars($info_grupo['nombre'] . ' ' . $info_grupo['apellido_paterno']); ?></td><td><strong>Periodo:</strong> <?php echo htmlspecialchars($info_grupo['nombre_periodo'] ?? 'N/A'); ?></td></tr>
        </table>
        <table class="calif-tabla">
            <thead>
                <tr><th>No.</th><th>Matrícula</th><th style="text-align: left;">Nombre del Alumno</th><th>U1</th><th>U2</th><th>U3</th><th>U4</th><th>U5</th><th>U6</th><th>FINAL</th></tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($alumnos as $al): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $al['matricula']; ?></td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($al['apellido_paterno'].' '.$al['nombre']); ?></td>
                        <td><?php echo $al['u1'] ?? '-'; ?></td><td><?php echo $al['u2'] ?? '-'; ?></td>
                        <td><?php echo $al['u3'] ?? '-'; ?></td><td><?php echo $al['u4'] ?? '-'; ?></td>
                        <td><?php echo $al['u5'] ?? '-'; ?></td><td><?php echo $al['u6'] ?? '-'; ?></td>
                        <td><strong><?php echo $al['calificacion'] ?? '-'; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="firmas">
            <div class="linea-firma"></div>
            <strong><?php echo htmlspecialchars($info_grupo['nombre'] . ' ' . $info_grupo['apellido_paterno']); ?></strong><br>
            Firma del Docente
        </div>
    </div>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>