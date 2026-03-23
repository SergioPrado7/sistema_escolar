<?php 
session_start(); 
require_once '../config/database.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();

$rol_actual = $_SESSION['rol'];
$id_usuario_actual = $_SESSION['id_usuario'];

$mi_horario = [];

// ==============================================================
// LÓGICA DE HORARIOS SEGÚN EL ROL
// ==============================================================
try {
    if ($rol_actual == 'Administrador') {
        $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h.hora_inicio, h.hora_fin, per.nombre_periodo, h.cupo_maximo
                  FROM horarios h 
                  JOIN materias m ON h.id_materia = m.id_materia 
                  JOIN grupos g ON h.id_grupo = g.id_grupo 
                  JOIN usuarios u ON h.id_profesor = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                  ORDER BY h.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rol_actual == 'Profesor') {
        $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h.hora_inicio, h.hora_fin, per.nombre_periodo, h.cupo_maximo
                  FROM horarios h 
                  JOIN materias m ON h.id_materia = m.id_materia 
                  JOIN grupos g ON h.id_grupo = g.id_grupo 
                  JOIN usuarios u ON h.id_profesor = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                  WHERE h.id_profesor = :id_usuario
                  ORDER BY h.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario_actual]);
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rol_actual == 'Alumno') {
        $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h.hora_inicio, h.hora_fin, per.nombre_periodo, h.cupo_maximo
                  FROM carga_academica ca 
                  JOIN horarios h ON ca.id_horario = h.id_horario 
                  JOIN materias m ON h.id_materia = m.id_materia 
                  JOIN grupos g ON h.id_grupo = g.id_grupo 
                  JOIN usuarios u ON h.id_profesor = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                  WHERE ca.id_alumno = :id_usuario
                  ORDER BY h.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario_actual]);
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    echo "Error al cargar el horario: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios - Tec San Pedro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/horarioIcono.ico">
    <link rel="stylesheet" href="../styles/estilo.css">
    
    <style>
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
        .main_contenido { margin-left: 0 !important; width: 100% !important; }

        /* MAGIA PARA IMPRIMIR: Oculta los menús y deja solo la tabla en blanco y negro */
        @media print {
            body { background-color: white !important; }
            .no-imprimir { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ccc !important; }
            table { border-collapse: collapse !important; width: 100% !important; }
            th, td { border: 1px solid #000 !important; color: #000 !important; }
        }
    </style>
</head>
<body class="bg-light">

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">
        
        <nav class="sidebar d-none d-md-flex no-imprimir">
            <div class="logo_foto">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
            </div>
            <div class="menu_links">
                <a href="dashboard.php" class="item">Panel Principal</a>
                <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                <?php endif; ?>
                <a href="calificaciones.php" class="item">Calificaciones</a>
                <a href="horarios.php" class="item active">Horarios</a> <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                <a href="gestion_academica.php" class="item">Gestión Académica</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                <a href="servicio_social.php" class="item">Servicio Social</a>
                <?php endif; ?>
            </div>
        </nav>

        <nav class="navbar navbar-dark d-md-none p-3 w-100 no-imprimir" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="text-white fw-bold">Horario de Clases</span>
                <div class="collapse navbar-collapse" id="menuMovil">
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="dashboard.php" class="item">Panel Principal</a>
                        <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                        <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                        <?php endif; ?>
                        <a href="calificaciones.php" class="item">Calificaciones</a>
                        <a href="horarios.php" class="item active">Horarios</a> <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                        <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                        <a href="gestion_academica.php" class="item">Gestión Académica</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                        <a href="servicio_social.php" class="item">Servicio Social</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main_contenido flex-grow-1 min-vh-100">
            <header class="top_header bg-white shadow-sm px-4 py-3 d-flex justify-content-between align-items-center no-imprimir">
                <h4 class="mb-0 fw-bold" style="color: var(--rojo-vino);">
                    <i class="bi bi-calendar3 me-2"></i> Mi Horario
                </h4>
                <div class="dropdown">
                    <div class="perfil dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                        <span class="fw-semibold text-secondary me-2 d-none d-sm-inline">Mi Cuenta</span>
                        <i class="bi bi-person-circle fs-3 text-secondary"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2">
                        <li><a class="dropdown-item text-danger fw-bold" href="../controllers/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </header>

            <section class="p-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold" style="color: #333;">Horario de Clases</h3>
                        <p class="text-muted mb-0">Revisa tus sesiones asignadas</p>
                    </div>
                    <button onclick="window.print()" class="btn text-white fw-bold mt-3 mt-md-0 no-imprimir shadow-sm" style="background-color: var(--rojo-vino);">
                        <i class="bi bi-printer-fill me-2"></i> Imprimir Horario
                    </button>
                </div>

                <div class="card shadow-sm border-0 borde-vino">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4 py-3">Horario</th>
                                        <th class="py-3">Materia</th>
                                        <th class="py-3">Grupo</th>
                                        <th class="py-3">Profesor</th>
                                        <?php if($rol_actual == 'Administrador'): ?>
                                            <th class="py-3">Periodo</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($mi_horario) > 0): ?>
                                        <?php foreach ($mi_horario as $clase): ?>
                                            <tr>
                                                <td class="px-4 fw-bold text-dark text-nowrap">
                                                    <i class="bi bi-clock text-secondary me-1 no-imprimir"></i> 
                                                    <?php echo htmlspecialchars(substr($clase['hora_inicio'], 0, 5) . ' - ' . substr($clase['hora_fin'], 0, 5)); ?>
                                                </td>
                                                <td class="fw-bold" style="color: var(--rojo-vino);"><?php echo htmlspecialchars($clase['nombre_materia']); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($clase['nombre_grupo']); ?></span></td>
                                                <td><?php echo htmlspecialchars($clase['profe_nombre'] . ' ' . $clase['apellido_paterno']); ?></td>
                                                <?php if($rol_actual == 'Administrador'): ?>
                                                    <td><small class="text-muted"><?php echo htmlspecialchars($clase['nombre_periodo']); ?></small></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                                Aún no hay clases registradas en tu horario.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>