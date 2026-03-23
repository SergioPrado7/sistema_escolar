<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Profesor', 'Administrador'])) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['horario']) || empty($_GET['horario'])) {
    header("Location: kardex.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();
$rol_actual = $_SESSION['rol'];
$id_horario = $_GET['horario'];

$query_grupo = "SELECT h.id_horario, m.nombre_materia, m.clave_materia, g.nombre_grupo, per.nombre_periodo 
                FROM horarios h 
                JOIN materias m ON h.id_materia = m.id_materia 
                JOIN grupos g ON h.id_grupo = g.id_grupo 
                LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                WHERE h.id_horario = :id_horario";
$stmt_grupo = $db->prepare($query_grupo);
$stmt_grupo->execute([':id_horario' => $id_horario]);
$info_grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if (!$info_grupo) {
    die("Error: El grupo solicitado no existe.");
}

$query_alumnos = "SELECT ca.*, u.matricula, p.nombre, p.apellido_paterno 
                  FROM carga_academica ca 
                  JOIN usuarios u ON ca.id_alumno = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  WHERE ca.id_horario = :id_horario 
                  ORDER BY p.apellido_paterno ASC";
$stmt_alumnos = $db->prepare($query_alumnos);
$stmt_alumnos->execute([':id_horario' => $id_horario]);
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Evaluaciones - Tec San Pedro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/gestionIcono.ico">
    <style>
        .final-score { font-size: 1.1em; font-weight: 900; }
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid p-0 d-flex flex-column flex-md-row">
    
    <nav class="sidebar d-none d-md-flex">
        <div class="logo_foto">
            <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
        </div>
        <div class="menu_links">
            <a href="dashboard.php" class="item">Panel Principal</a>
            <?php if ($rol_actual == 'Administrador'): ?>
                <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
            <?php endif; ?>
            <a href="calificaciones.php" class="item">Calificaciones</a>
            <?php if ($rol_actual == 'Administrador'): ?>
                <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                <a href="gestion_academica.php" class="item">Gestión Académica</a>
                <a href="servicio_social.php" class="item">Servicio Social</a>
            <?php endif; ?>
            <a href="kardex.php" class="item active">Kardex</a>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil"><span class="navbar-toggler-icon"></span></button>
            <span class="text-white fw-bold">Evaluaciones del Grupo</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <a href="calificaciones.php" class="item active">Calificaciones</a>
                    <a href="kardex.php" class="item active">Kardex</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-card-checklist me-2"></i>Evaluaciones del Grupo</h1>
                    <p class="text-muted mb-0">Vista de solo lectura del curso finalizado.</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 borde-vino">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($info_grupo['nombre_materia']); ?></h4>
                        <span class="badge bg-secondary me-2">Clave: <?php echo htmlspecialchars($info_grupo['clave_materia']); ?></span>
                        <span class="text-muted"><i class="bi bi-people-fill me-1"></i> Grupo: <strong><?php echo htmlspecialchars($info_grupo['nombre_grupo']); ?></strong></span>
                    </div>
                    <div class="text-end">
                        <span class="d-block text-muted small text-uppercase fw-bold">Periodo</span>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($info_grupo['nombre_periodo'] ?? 'No especificado'); ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-list-ol me-2"></i>Calificaciones Finales</h5>
                    <a href="acta_calificaciones.php?horario=<?php echo $id_horario; ?>" target="_blank" class="btn btn-sm text-white shadow-sm" style="background-color: var(--rojo-vino);">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Acta
                    </a>
                </div>
                <div class="card-body p-0 mt-3">
                    <?php if (count($alumnos) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background-color: #f8f9fa; color: var(--rojo-vino);">
                                    <tr>
                                        <th class="ps-4 py-3">Matrícula</th>
                                        <th class="py-3">Nombre del Alumno</th>
                                        <th class="text-center py-3">U1</th>
                                        <th class="text-center py-3">U2</th>
                                        <th class="text-center py-3">U3</th>
                                        <th class="text-center py-3">U4</th>
                                        <th class="text-center py-3">U5</th>
                                        <th class="text-center py-3">U6</th>
                                        <th class="text-center py-3">Promedio</th>
                                        <th class="pe-4 text-center py-3">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alumnos as $alumno): ?>
                                        <?php $aprobado = ($alumno['calificacion'] !== null && $alumno['calificacion'] >= 70); ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted"><?php echo htmlspecialchars($alumno['matricula']); ?></td>
                                            <td class="fw-bold text-dark py-3"><?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['nombre']); ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u1'] ?? '-'; ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u2'] ?? '-'; ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u3'] ?? '-'; ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u4'] ?? '-'; ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u5'] ?? '-'; ?></td>
                                            <td class="text-center text-secondary fw-semibold"><?php echo $alumno['u6'] ?? '-'; ?></td>
                                            
                                            <td class="text-center final-score <?php echo $aprobado ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $alumno['calificacion'] ?? '-'; ?>
                                            </td>
                                            <td class="pe-4 text-center">
                                                <?php if($aprobado): ?>
                                                    <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> Aprobado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle me-1"></i> Reprobado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people-fill fs-1 d-block mb-3"></i>
                            <h5>No hay registros de alumnos para este grupo.</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>