<?php
session_start();
require_once '../config/database.php';

// Verificamos seguridad
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../index.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();

// Traemos los catálogos básicos para llenar las tablas y selects
$periodos = $db->query("SELECT * FROM periodos ORDER BY id_periodo DESC")->fetchAll(PDO::FETCH_ASSOC);
$materias = $db->query("SELECT * FROM materias")->fetchAll(PDO::FETCH_ASSOC);
$profesores = $db->query("SELECT u.id_usuario, p.nombre, p.apellido_paterno FROM usuarios u INNER JOIN personas p ON u.id_usuario = p.id_usuario WHERE u.rol = 'Profesor'")->fetchAll(PDO::FETCH_ASSOC);

$horarios_activos = $db->query("SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, per.nombre_periodo, h.cupo_maximo, h.hora_inicio, h.hora_fin 
                                FROM horarios h 
                                INNER JOIN materias m ON h.id_materia = m.id_materia 
                                INNER JOIN grupos g ON h.id_grupo = g.id_grupo 
                                INNER JOIN usuarios u ON h.id_profesor = u.id_usuario 
                                INNER JOIN personas p ON u.id_usuario = p.id_usuario 
                                LEFT JOIN periodos per ON h.id_periodo = per.id_periodo")->fetchAll(PDO::FETCH_ASSOC);

$alumno_encontrado = null;
$materias_inscritas = [];
$materias_disponibles = [];

if (isset($_GET['matricula']) && !empty($_GET['matricula'])) {
    $matricula = trim($_GET['matricula']);
    
    // 1. Buscamos a Kevin (o al alumno que pongan)
    $stmt = $db->prepare("SELECT u.id_usuario, u.matricula, p.nombre, p.apellido_paterno 
                          FROM usuarios u 
                          INNER JOIN personas p ON u.id_usuario = p.id_usuario 
                          WHERE u.matricula = :matricula AND u.rol = 'Alumno'");
    $stmt->execute([':matricula' => $matricula]);
    $alumno_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alumno_encontrado) {
        $id_alumno = $alumno_encontrado['id_usuario'];

        // 2. Traemos su horario/materias inscritas
        $query_inscritas = "SELECT ca.id_carga, m.nombre_materia, p.nombre as profe_nombre, p.apellido_paterno as profe_apellido, g.nombre_grupo, ca.calificacion 
                            FROM carga_academica ca 
                            INNER JOIN horarios h ON ca.id_horario = h.id_horario 
                            INNER JOIN materias m ON h.id_materia = m.id_materia 
                            INNER JOIN usuarios u_profe ON h.id_profesor = u_profe.id_usuario 
                            INNER JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                            INNER JOIN grupos g ON h.id_grupo = g.id_grupo 
                            WHERE ca.id_alumno = :id_alumno";
        $stmt_inscritas = $db->prepare($query_inscritas);
        $stmt_inscritas->execute([':id_alumno' => $id_alumno]);
        $materias_inscritas = $stmt_inscritas->fetchAll(PDO::FETCH_ASSOC);

        // 3. Traemos las clases disponibles para el modal de asignar
        $query_disponibles = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno as profe_apellido 
                              FROM horarios h 
                              INNER JOIN materias m ON h.id_materia = m.id_materia 
                              INNER JOIN usuarios u_profe ON h.id_profesor = u_profe.id_usuario 
                              INNER JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                              INNER JOIN grupos g ON h.id_grupo = g.id_grupo";
        $materias_disponibles = $db->query($query_disponibles)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Académica - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        /* Estilos específicos para las pestañas (Tabs) adaptados a tu tema */
        .nav-tabs .nav-link.active { 
            color: var(--rojo-vino) !important; 
            font-weight: bold; 
            border-bottom: 3px solid var(--rojo-vino) !important; 
            border-top: none; border-left: none; border-right: none;
            background-color: transparent;
        }
        .nav-tabs .nav-link { color: #6c757d; border: none; font-weight: 500; }
        .nav-tabs .nav-link:hover { color: var(--rojo-vino); border-color: transparent; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
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
            <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
            <a href="calificaciones.php" class="item">Calificaciones</a>
            <a href="finanzas.php" class="item">Finanzas y Pagos</a>
            <a href="gestion_academica.php" class="item active">Gestión Academica</a>
            <a href="servicio_social.php" class="item">Servicio Social</a>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Gestión Académica</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                    <a href="calificaciones.php" class="item">Calificaciones</a>
                    <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                    <a href="gestion_academica.php" class="item active">Gestión Academica</a>
                    <a href="servicio_social.php" class="item">Servicio Social</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;">Gestión Académica</h1>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    
                    <ul class="nav nav-tabs mb-4" id="academicTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active px-4" id="inscripciones-tab" data-bs-toggle="tab" data-bs-target="#inscripciones" type="button"><i class="bi bi-person-badge me-2"></i>Inscripciones</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4" id="grupos-tab" data-bs-toggle="tab" data-bs-target="#grupos" type="button"><i class="bi bi-calendar3 me-2"></i>Grupos y Horarios</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4" id="materias-tab" data-bs-toggle="tab" data-bs-target="#materias" type="button"><i class="bi bi-book me-2"></i>Catálogo Materias</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4" id="periodos-tab" data-bs-toggle="tab" data-bs-target="#periodos" type="button"><i class="bi bi-clock-history me-2"></i>Periodos</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="academicTabsContent">
                        
                        <div class="tab-pane fade show active" id="inscripciones" role="tabpanel">
                            
                            <form action="" method="GET" class="mb-4 mt-2">
                                <div class="row g-2 align-items-center">
                                    <div class="col-12 col-md-8 col-lg-6">
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" name="matricula" class="form-control border-start-0" placeholder="Buscar Matrícula de Alumno..." value="<?php echo isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : ''; ?>" required>
                                            <button type="submit" class="btn text-white px-4" style="background-color: var(--rojo-vino);">Buscar Alumno</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <?php if (isset($_GET['matricula'])): ?>
                                <?php if ($alumno_encontrado): ?>
                                    
                                    <div class="alert shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4" style="background-color: #f8f9fa; border-left: 5px solid var(--rojo-vino);">
                                        <div class="mb-3 mb-md-0">
                                            <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($alumno_encontrado['nombre'] . ' ' . $alumno_encontrado['apellido_paterno']); ?></h5>
                                            <span class="text-muted"><i class="bi bi-person-vcard me-1"></i> Matrícula: <strong><?php echo htmlspecialchars($alumno_encontrado['matricula']); ?></strong></span>
                                        </div>
                                        <button class="btn text-white shadow-sm" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalAsignar">
                                            <i class="bi bi-plus-circle me-1"></i> Asignar Materia
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead style="color: var(--rojo-vino);">
                                                <tr>
                                                    <th>Materia</th>
                                                    <th>Profesor</th>
                                                    <th>Grupo</th>
                                                    <th>Calificación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($materias_inscritas) > 0): ?>
                                                    <?php foreach ($materias_inscritas as $materia): ?>
                                                        <tr>
                                                            <td class="fw-bold"><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
                                                            <td><?php echo htmlspecialchars($materia['profe_nombre'] . ' ' . $materia['profe_apellido']); ?></td>
                                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($materia['nombre_grupo']); ?></span></td>
                                                            <td>
                                                                <?php echo $materia['calificacion'] !== null ? '<span class="fw-bold text-dark">'.htmlspecialchars($materia['calificacion']).'</span>' : '<span class="text-muted small"><i class="bi bi-dash-circle"></i> Sin calificar</span>'; ?>
                                                            </td>
                                                            <td>
                                                                <a href="../controllers/eliminar_carga.php?id_carga=<?php echo $materia['id_carga']; ?>&matricula=<?php echo urlencode($alumno_encontrado['matricula']); ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('¿Seguro que deseas eliminar esta materia del alumno?');" title="Eliminar materia"><i class="bi bi-trash"></i></a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-5 text-muted">
                                                            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                                            Este alumno no tiene materias inscritas aún.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                <?php else: ?>
                                    <div class="alert alert-danger text-center shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> No se encontró ningún alumno con esa matrícula.</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-light border text-center text-muted py-5 shadow-sm">
                                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                                    Ingresa una matrícula en el buscador para gestionar sus materias.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="grupos" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
                                <h5 class="fw-bold text-dark mb-0">Grupos Aperturados</h5>
                                <button class="btn text-white shadow-sm" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalNuevoGrupo"><i class="bi bi-plus-lg me-1"></i> Abrir Grupo</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead style="color: var(--rojo-vino);">
                                        <tr>
                                            <th>Periodo</th>
                                            <th>Materia</th>
                                            <th>Grupo</th>
                                            <th>Profesor</th>
                                            <th>Horario</th> 
                                            <th>Cupo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($horarios_activos) > 0): ?>
                                            <?php foreach($horarios_activos as $horario): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($horario['nombre_periodo'] ?? 'N/A'); ?></span></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($horario['nombre_materia']); ?></td>
                                                <td><?php echo htmlspecialchars($horario['nombre_grupo']); ?></td>
                                                <td><?php echo htmlspecialchars($horario['profe_nombre'] . ' ' . $horario['apellido_paterno']); ?></td>
                                                <td class="text-muted">
                                                    <i class="bi bi-clock me-1"></i> 
                                                    <?php echo htmlspecialchars(substr($horario['hora_inicio'], 0, 5) . ' - ' . substr($horario['hora_fin'], 0, 5)); ?>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($horario['cupo_maximo'] ?? '30'); ?></strong></td>
                                                <td>
                                                    <a href="../controllers/eliminar_horario.php?id=<?php echo $horario['id_horario']; ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('¿Seguro que deseas eliminar este grupo?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Aún no hay grupos configurados.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="materias" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
                                <h5 class="fw-bold text-dark mb-0">Catálogo de Materias</h5>
                                <button class="btn text-white shadow-sm" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalNuevaMateria"><i class="bi bi-plus-lg me-1"></i> Nueva Materia</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead style="color: var(--rojo-vino);">
                                        <tr>
                                            <th>Clave</th>
                                            <th>Materia</th>
                                            <th>Créditos</th>
                                            <th>Semestre</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($materias) > 0): ?>
                                            <?php foreach($materias as $mat): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?php echo htmlspecialchars($mat['clave_materia']); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($mat['nombre_materia']); ?></td>
                                                <td><?php echo htmlspecialchars($mat['creditos']); ?> cr</td>
                                                <td>Semestre <?php echo htmlspecialchars($mat['semestre_sugerido']); ?></td> 
                                                <td>
                                                    <a href="../controllers/eliminar_materia.php?id=<?php echo $mat['id_materia']; ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('¿Seguro que deseas eliminar esta materia del catálogo?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-5">No hay materias registradas en el sistema.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="periodos" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
                                <h5 class="fw-bold text-dark mb-0">Ciclos Escolares</h5>
                                <button class="btn text-white shadow-sm" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalNuevoPeriodo"><i class="bi bi-plus-lg me-1"></i> Nuevo Periodo</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead style="color: var(--rojo-vino);">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre del Periodo</th>
                                            <th>Fechas</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($periodos) > 0): ?>
                                            <?php foreach($periodos as $per): ?>
                                            <tr>
                                                <td class="text-muted"><?php echo $per['id_periodo']; ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($per['nombre_periodo'] ?? 'No definido'); ?></td>
                                                <td class="text-muted"><i class="bi bi-calendar-range me-1"></i> <?php echo $per['fecha_inicio'] . ' al ' . $per['fecha_fin']; ?></td>
                                                <td><span class="badge bg-success">Activo</span></td>
                                                <td>
                                                    <a href="../controllers/eliminar_periodo.php?id=<?php echo $per['id_periodo']; ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('¿Seguro que deseas eliminar este ciclo escolar?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-5">No hay periodos escolares registrados.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if ($alumno_encontrado): ?>
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
                <h5 class="modal-title">Asignar Materia a <?php echo htmlspecialchars($alumno_encontrado['nombre']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/guardar_carga.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_alumno" value="<?php echo $alumno_encontrado['id_usuario']; ?>">
                    <input type="hidden" name="matricula_buscada" value="<?php echo htmlspecialchars($alumno_encontrado['matricula']); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecciona Clase Disponible:</label>
                        <select name="id_horario" class="form-select" required>
                            <option value="">-- Elige una opción --</option>
                            <?php foreach ($materias_disponibles as $disponible): ?>
                                <option value="<?php echo $disponible['id_horario']; ?>">
                                    <?php echo htmlspecialchars($disponible['nombre_materia'] . ' - Grupo ' . $disponible['nombre_grupo'] . ' (' . $disponible['profe_nombre'] . ' ' . $disponible['profe_apellido'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--rojo-vino);">Guardar en Carga</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalNuevaMateria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
                <h5 class="modal-title">Registrar Materia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/guardar_materia.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clave (Ej. ISC-101)</label>
                        <input type="text" name="clave" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Materia</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Créditos</label>
                            <input type="number" name="creditos" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Semestre Sugerido</label>
                            <input type="number" name="semestre" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--rojo-vino);">Guardar Materia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoPeriodo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
                <h5 class="modal-title">Registrar Ciclo Escolar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/guardar_periodo.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Código del Periodo</label>
                        <input type="text" name="codigo_periodo" class="form-control" placeholder="Ej. 2026-1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Periodo</label>
                        <input type="text" name="nombre_periodo" class="form-control" placeholder="Ej. Enero - Junio 2026" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Fecha de Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--rojo-vino);">Guardar Periodo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoGrupo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
                <h5 class="modal-title">Aperturar Nuevo Grupo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/guardar_grupo.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Periodo Escolar</label>
                        <select name="id_periodo" class="form-select" required>
                            <option value="">-- Selecciona el Periodo --</option>
                            <?php foreach($periodos as $per): ?>
                                <option value="<?php echo $per['id_periodo']; ?>"><?php echo htmlspecialchars($per['nombre_periodo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Materia</label>
                        <select name="id_materia" class="form-select" required>
                            <option value="">-- Selecciona la Materia --</option>
                            <?php foreach($materias as $mat): ?>
                                <option value="<?php echo $mat['id_materia']; ?>"><?php echo htmlspecialchars($mat['nombre_materia']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Profesor Asignado</label>
                        <select name="id_profesor" class="form-select" required>
                            <option value="">-- Selecciona un Profesor --</option>
                            <?php foreach($profesores as $profe): ?>
                                <option value="<?php echo $profe['id_usuario']; ?>"><?php echo htmlspecialchars($profe['nombre'] . ' ' . $profe['apellido_paterno']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label fw-bold">Grupo</label>
                            <input type="text" name="nombre_grupo" class="form-control" placeholder="Ej. 8A" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-bold">Hora Inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-bold">Hora Fin</label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cupo Máximo</label>
                        <input type="number" name="cupo_maximo" class="form-control" value="30" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--rojo-vino);">Abrir Grupo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Ver si el navegador guardó en su memoria la última pestaña que visitaste
        let pestañaGuardada = localStorage.getItem('pestañaActivaAdmin');
        if (pestañaGuardada) {
            let botonPestaña = document.querySelector('button[data-bs-target="' + pestañaGuardada + '"]');
            if (botonPestaña) {
                let tab = new bootstrap.Tab(botonPestaña);
                tab.show(); // Forzamos a que se abra esa
            }
        }

        // 2. Cada vez que le piques a una pestaña nueva, la guardamos en la memoria
        let botonesTab = document.querySelectorAll('button[data-bs-toggle="tab"]');
        botonesTab.forEach(function(boton) {
            boton.addEventListener('shown.bs.tab', function(e) {
                localStorage.setItem('pestañaActivaAdmin', e.target.getAttribute('data-bs-target'));
            });
        });
    });
</script>
</body>
</html>