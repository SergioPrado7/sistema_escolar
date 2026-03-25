<?php 
session_start(); 
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 'Administrador') {
    header("Location: dashboard.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();

$rol_actual = $_SESSION['rol'];
$id_usuario_actual = $_SESSION['id_usuario'];
$historial = [];
$promedio_kardex = 0;

// ==============================================================
// 1. OBTENER HISTORIAL (CURSOS FINALIZADOS)
// ==============================================================
if ($rol_actual == 'Profesor') {
    // SE AGREGA GROUP BY PARA NO CLONAR LOS CURSOS DEL PROFE
    $query = "SELECT MAX(h.id_horario) as id_horario, m.nombre_materia, m.clave_materia, g.nombre_grupo, 
                     MAX((SELECT MAX(finalizado) FROM carga_academica WHERE id_horario = h.id_horario)) as curso_finalizado
              FROM horarios h 
              JOIN materias m ON h.id_materia = m.id_materia 
              JOIN grupos g ON h.id_grupo = g.id_grupo 
              WHERE h.id_profesor = :id_profesor
              GROUP BY g.id_grupo, m.nombre_materia, m.clave_materia, g.nombre_grupo
              HAVING curso_finalizado = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':id_profesor' => $id_usuario_actual]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($rol_actual == 'Alumno') {
    // SE AGREGA GROUP BY PARA NO CLONAR LAS MATERIAS DEL ALUMNO
    $query = "SELECT m.nombre_materia, m.clave_materia, m.creditos, per.nombre_periodo, ca.calificacion
              FROM carga_academica ca 
              JOIN horarios h ON ca.id_horario = h.id_horario 
              JOIN materias m ON h.id_materia = m.id_materia 
              LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
              WHERE ca.id_alumno = :id_alumno AND ca.finalizado = 1
              GROUP BY h.id_grupo, m.nombre_materia, m.clave_materia, m.creditos, per.nombre_periodo, ca.calificacion
              ORDER BY per.id_periodo DESC, m.nombre_materia ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':id_alumno' => $id_usuario_actual]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sacar promedio histórico
    $suma = 0; $total_materias = count($historial);
    foreach ($historial as $h) { $suma += floatval($h['calificacion']); }
    $promedio_kardex = ($total_materias > 0) ? round($suma / $total_materias, 2) : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($rol_actual == 'Alumno') ? 'Mi Kardex' : 'Historial de Cursos'; ?> - Tec San Pedro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/gestionIcono.ico">
    <style>
        .promedio-box {
            background-color: var(--rojo-vino);
            color: white;
            border-radius: 10px;
            padding: 10px 25px;
            text-align: center;
        }
        .tarjeta-hover { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .tarjeta-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(128, 0, 32, 0.15) !important; }
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
    </style>
</head>
<body>

<div class="container-fluid p-0 d-flex flex-column flex-md-row">
    
    <nav class="sidebar d-none d-md-flex">
        <div class="logo_foto">
            <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
        </div>
        <div class="menu_links">
            <a href="dashboard.php" class="item">Panel Principal</a>
            <a href="calificaciones.php" class="item">Calificaciones</a>
            <?php if ($rol_actual == 'Alumno'): ?>
                <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                <a href="servicio_social.php" class="item">Servicio Social</a>
                <a href="kardex.php" class="item active">Kardex</a>
            <?php endif; ?>
            <?php if ($rol_actual == 'Profesor'): ?>
                <a href="kardex.php" class="item active">Kardex</a>
            <?php endif; ?>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Kardex</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <a href="calificaciones.php" class="item">Calificaciones</a>
                    <?php if ($rol_actual == 'Alumno'): ?>
                        <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                        <a href="servicio_social.php" class="item">Servicio Social</a>
                        <a href="kardex.php" class="item active">Kardex</a>
                    <?php endif; ?>
                    <?php if ($rol_actual == 'Profesor'): ?>
                        <a href="kardex.php" class="item active">Kardex</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">

            <?php if ($rol_actual == 'Alumno'): ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-archive-fill me-2"></i>Mi Kardex</h1>
                        <p class="text-muted mb-0">Historial oficial de materias finalizadas.</p>
                    </div>
                    <div class="promedio-box shadow-sm d-none d-md-block">
                        <span class="d-block" style="font-size: 0.7em; text-transform: uppercase; letter-spacing: 1px;">Promedio</span>
                        <span class="fw-bold" style="font-size: 1.8em;"><?php echo $promedio_kardex; ?></span>
                    </div>
                </div>

                <div class="promedio-box shadow-sm d-block d-md-none mb-4">
                    <span class="d-block" style="font-size: 0.7em; text-transform: uppercase;">Promedio</span>
                    <span class="fw-bold" style="font-size: 1.8em;"><?php echo $promedio_kardex; ?></span>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background-color: #f8f9fa; color: var(--rojo-vino);">
                                    <tr>
                                        <th class="ps-4 py-3">Clave</th>
                                        <th class="py-3">Materia</th>
                                        <th class="py-3">Periodo</th>
                                        <th class="py-3 text-center">Créditos</th>
                                        <th class="py-3 text-center">Calificación Final</th>
                                        <th class="pe-4 py-3 text-center">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($historial) > 0): ?>
                                        <?php foreach ($historial as $mat): ?>
                                            <?php $aprobado = ($mat['calificacion'] >= 70); ?>
                                            <tr>
                                                <td class="ps-4 text-muted fw-semibold"><?php echo htmlspecialchars($mat['clave_materia']); ?></td>
                                                <td class="fw-bold text-dark py-3"><?php echo htmlspecialchars($mat['nombre_materia']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($mat['nombre_periodo'] ?? 'N/A'); ?></span></td>
                                                <td class="text-center text-muted"><?php echo $mat['creditos']; ?></td>
                                                <td class="text-center fs-5 fw-bold <?php echo $aprobado ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo htmlspecialchars($mat['calificacion']); ?>
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
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                                                No tienes materias finalizadas en tu historial.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($rol_actual == 'Profesor'): ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-journal-check me-2"></i>Kardex Materias</h1>
                        <p class="text-muted mb-0">Consulta e imprime las actas de tus grupos finalizados.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <?php if (count($historial) > 0): ?>
                        <?php foreach ($historial as $clase): ?>
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <div class="card shadow-sm border-0 h-100 tarjeta-hover borde-vino bg-white">
                                    <div class="card-body p-4 text-center d-flex flex-column">
                                        <i class="bi bi-archive text-secondary fs-1 mb-2"></i>
                                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($clase['nombre_materia']); ?></h5>
                                        <p class="text-muted small mb-4"><span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($clase['clave_materia']); ?></span> Grupo: <strong><?php echo htmlspecialchars($clase['nombre_grupo']); ?></strong></p>
                                        
                                        <div class="mt-auto d-grid gap-2">
                                            <a href="ver_evaluaciones.php?horario=<?php echo $clase['id_horario']; ?>" class="btn btn-sm btn-outline-secondary fw-semibold">
                                                <i class="bi bi-eye-fill me-1"></i> Ver Evaluaciones
                                            </a>
                                            <a href="acta_calificaciones.php?horario=<?php echo $clase['id_horario']; ?>" target="_blank" class="btn btn-sm btn-secondary fw-semibold">
                                                <i class="bi bi-printer-fill me-1"></i> Imprimir Acta
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-light border text-center py-5 shadow-sm">
                                <i class="bi bi-archive-fill text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-secondary">Aún no has finalizado ningún curso.</h5>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>