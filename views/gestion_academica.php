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
    <title>Gestión Académica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --rojo-vino: #800020; }
        .bg-vino { background-color: var(--rojo-vino) !important; color: white; }
        .text-vino { color: var(--rojo-vino) !important; }
        .btn-vino { background-color: var(--rojo-vino); color: white; border: none; }
        .btn-vino:hover { background-color: #600018; color: white; }
        .nav-tabs .nav-link.active { color: var(--rojo-vino); font-weight: bold; border-bottom: 3px solid var(--rojo-vino); }
        .nav-tabs .nav-link { color: #6c757d; border: none; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-vino d-md-none p-3 w-100">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Gestión Académica</span>
        </div>
    </nav>

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">
        
        <nav class="sidebar d-none d-md-flex flex-column p-3 bg-vino min-vh-100" style="width: 250px;">
            <div class="text-center mb-4">
                <h4 class="text-white fw-bold mt-2">Tec San Pedro</h4>
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="dashboard.php" class="btn btn-outline-light text-start border-0"><i class="bi bi-house me-2"></i>Inicio</a>
                <a href="gestion_usuarios.php" class="btn btn-outline-light text-start border-0"><i class="bi bi-people me-2"></i>Usuarios</a>
                <a href="gestion_academica.php" class="btn btn-light text-vino text-start fw-bold"><i class="bi bi-journal-bookmark me-2"></i>Academia</a>
                </div>
        </nav>

        <main class="flex-grow-1 p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-vino fw-bold mb-0">Centro de Operaciones Académicas</h2>
            </div>

            <div class="card card-custom p-3 p-md-4">
                <ul class="nav nav-tabs mb-4" id="academicTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inscripciones-tab" data-bs-toggle="tab" data-bs-target="#inscripciones" type="button">Inscripciones</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="grupos-tab" data-bs-toggle="tab" data-bs-target="#grupos" type="button">Grupos y Horarios</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="materias-tab" data-bs-toggle="tab" data-bs-target="#materias" type="button">Catálogo Materias</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="periodos-tab" data-bs-toggle="tab" data-bs-target="#periodos" type="button">Periodos</button>
                    </li>
                </ul>

                <div class="tab-content" id="academicTabsContent">
                    
                    <div class="tab-pane fade show active" id="inscripciones" role="tabpanel">
        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold"><i class="bi bi-person-badge"></i> Carga Académica de Alumnos</h5>
                        </div>

                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" name="matricula" class="form-control" placeholder="Buscar Matrícula de Alumno..." value="<?php echo isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : ''; ?>" required>
                                <button type="submit" class="btn btn-vino">Buscar Alumno</button>
                            </div>
                        </form>

                        <?php if (isset($_GET['matricula'])): ?>
                            <?php if ($alumno_encontrado): ?>
                                
                                <div class="alert alert-success d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Alumno:</strong> <?php echo htmlspecialchars($alumno_encontrado['nombre'] . ' ' . $alumno_encontrado['apellido_paterno']); ?> <br>
                                        <strong>Matrícula:</strong> <?php echo htmlspecialchars($alumno_encontrado['matricula']); ?>
                                    </div>
                                    <button class="btn btn-sm btn-vino" data-bs-toggle="modal" data-bs-target="#modalAsignar">
                                        <i class="bi bi-plus-circle"></i> Asignar Materia
                                    </button>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
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
                                                        <td><?php echo htmlspecialchars($materia['nombre_grupo']); ?></td>
                                                        <td>
                                                            <?php echo $materia['calificacion'] !== null ? htmlspecialchars($materia['calificacion']) : '<span class="text-muted">Sin calificar</span>'; ?>
                                                        </td>
                                                        <td>
                                                            <a href="../controllers/eliminar_carga.php?id_carga=<?php echo $materia['id_carga']; ?>&matricula=<?php echo urlencode($alumno_encontrado['matricula']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar esta materia?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Este alumno no tiene materias inscritas aún.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="modal fade" id="modalAsignar" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-vino text-white">
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
                                                    <button type="submit" class="btn btn-vino">Guardar en Carga</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-danger text-center"><i class="bi bi-exclamation-triangle"></i> No se encontró ningún alumno con esa matrícula.</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary text-center">Ingresa una matrícula en el buscador para gestionar sus materias.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="grupos" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold"><i class="bi bi-calendar3"></i> Gestión de Grupos Aperturados</h5>
                            <button class="btn btn-vino btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoGrupo"><i class="bi bi-plus-lg"></i> Abrir Grupo</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Periodo</th>
                                        <th>Materia</th>
                                        <th>Grupo</th>
                                        <th>Profesor</th>
                                        <th>Cupo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="text-center text-muted">Aún no hay grupos configurados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="materias" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold"><i class="bi bi-book"></i> Catálogo de Materias</h5>
                            <button class="btn btn-vino btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaMateria"><i class="bi bi-plus-lg"></i> Nueva Materia</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Clave</th>
                                        <th>Materia</th>
                                        <th>Créditos</th>
                                        <th>Semestre</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($materias as $mat): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($mat['clave_materia']); ?></td>
                                        <td><?php echo htmlspecialchars($mat['nombre_materia']); ?></td>
                                        <td><?php echo htmlspecialchars($mat['creditos']); ?></td>
                                        <td><?php echo htmlspecialchars($mat['semestre_sugerido']); ?></td>
                                        <td><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="periodos" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold"><i class="bi bi-clock-history"></i> Ciclos Escolares</h5>
                            <button class="btn btn-vino btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoPeriodo"><i class="bi bi-plus-lg"></i> Nuevo Periodo</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre del Periodo</th>
                                        <th>Fechas</th>
                                        <th>Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($periodos as $per): ?>
                                    <tr>
                                        <td><?php echo $per['id_periodo']; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($per['nombre_periodo'] ?? 'No definido'); ?></td>
                                        <td><?php echo $per['fecha_inicio'] . ' a ' . $per['fecha_fin']; ?></td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalNuevaMateria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-vino text-white">
                    <h5 class="modal-title">Registrar Materia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="../controllers/guardar_materia.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Clave (Ej. ISC-101)</label>
                            <input type="text" name="clave" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nombre de la Materia</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>Créditos</label>
                                <input type="number" name="creditos" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>Semestre Sugerido</label>
                                <input type="number" name="semestre" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-vino">Guardar Materia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>