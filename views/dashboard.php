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

$cursos_activos = [];

// ==============================================================
// 1. OBTENER SOLO CURSOS ACTIVOS PARA EL DASHBOARD
// ==============================================================
if ($rol_actual == 'Profesor') {
    $query = "SELECT h.id_horario, m.nombre_materia, m.clave_materia, g.nombre_grupo, 
                     IFNULL((SELECT MAX(finalizado) FROM carga_academica WHERE id_horario = h.id_horario), 0) as curso_finalizado
              FROM horarios h 
              JOIN materias m ON h.id_materia = m.id_materia 
              JOIN grupos g ON h.id_grupo = g.id_grupo 
              WHERE h.id_profesor = :id_profesor";
    $stmt = $db->prepare($query);
    $stmt->execute([':id_profesor' => $id_usuario_actual]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $curso) {
        if ($curso['curso_finalizado'] == 0) {
            $cursos_activos[] = $curso;
        }
    }

} elseif ($rol_actual == 'Alumno') {
    $query = "SELECT m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, ca.finalizado
              FROM carga_academica ca 
              JOIN horarios h ON ca.id_horario = h.id_horario 
              JOIN materias m ON h.id_materia = m.id_materia 
              JOIN grupos g ON h.id_grupo = g.id_grupo 
              JOIN usuarios u ON h.id_profesor = u.id_usuario 
              JOIN personas p ON u.id_usuario = p.id_usuario 
              WHERE ca.id_alumno = :id_alumno";
    $stmt = $db->prepare($query);
    $stmt->execute([':id_alumno' => $id_usuario_actual]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $curso) {
        if ($curso['finalizado'] == 0) {
            $cursos_activos[] = $curso;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Tec San Pedro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        .tarjeta-hover { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .tarjeta-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(128, 0, 32, 0.15) !important; }
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
        .icono-gigante { font-size: 3.5rem; color: var(--rojo-vino); margin-bottom: 10px; }
        .main_contenido { margin-left: 0 !important; width: 100% !important; }
    </style>
</head>
<body class="bg-light">

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">
        
        <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
            <div class="container-fluid justify-content-center">
                <span class="text-white fw-bold fs-5">Gestor Tec San Pedro</span>
            </div>
        </nav>

        <main class="main_contenido flex-grow-1 min-vh-100">
            <header class="top_header bg-white shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: var(--rojo-vino);">
                    <?php if ($rol_actual == 'Administrador'): ?> <i class="bi bi-shield-lock-fill me-2"></i> Panel de Administración
                    <?php elseif ($rol_actual == 'Profesor'): ?> <i class="bi bi-person-workspace me-2"></i> Panel de Docentes
                    <?php else: ?> <i class="bi bi-mortarboard-fill me-2"></i> Portal del Alumno <?php endif; ?>
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
                
                <div class="text-center mb-5 mt-2">
                    <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
                    <h2 class="fw-bold" style="color: #333;">Bienvenido(a), <?php echo htmlspecialchars($rol_actual); ?></h2>
                    <p class="text-muted">¿Qué deseas hacer el día de hoy?</p>
                </div>

                <?php if ($rol_actual == 'Administrador'): ?>
                    <div class="row g-4 justify-content-center">
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3"><a href="gestion_usuarios.php" class="text-decoration-none"><div class="card shadow-sm border-0 h-100 text-center p-4 tarjeta-hover borde-vino"><i class="bi bi-people-fill icono-gigante"></i><h5 class="fw-bold text-dark mt-2 mb-0">Gestión Usuarios</h5></div></a></div>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3"><a href="gestion_academica.php" class="text-decoration-none"><div class="card shadow-sm border-0 h-100 text-center p-4 tarjeta-hover borde-vino"><i class="bi bi-journal-bookmark-fill icono-gigante"></i><h5 class="fw-bold text-dark mt-2 mb-0">Gestión Académica</h5></div></a></div>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3"><a href="calificaciones.php" class="text-decoration-none"><div class="card shadow-sm border-0 h-100 text-center p-4 tarjeta-hover borde-vino"><i class="bi bi-award-fill icono-gigante"></i><h5 class="fw-bold text-dark mt-2 mb-0">Calificaciones</h5></div></a></div>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3"><a href="finanzas.php" class="text-decoration-none"><div class="card shadow-sm border-0 h-100 text-center p-4 tarjeta-hover borde-vino"><i class="bi bi-cash-coin icono-gigante"></i><h5 class="fw-bold text-dark mt-2 mb-0">Finanzas</h5></div></a></div>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3"><a href="servicio_social.php" class="text-decoration-none"><div class="card shadow-sm border-0 h-100 text-center p-4 tarjeta-hover borde-vino"><i class="bi bi-building-check icono-gigante"></i><h5 class="fw-bold text-dark mt-2 mb-0">Servicio Social</h5></div></a></div>
                    </div>

                <?php elseif ($rol_actual == 'Profesor'): ?>
                    
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 max-w-1200 mx-auto">
                        <h4 class="fw-bold mb-3 mb-md-0 text-dark">Mis Grupos Activos</h4>
                        <a href="kardex.php" class="btn btn-outline-secondary fw-bold shadow-sm"><i class="bi bi-archive-fill me-2"></i>Historial de Cursos (Actas)</a>
                    </div>

                    <div class="row g-4 justify-content-center max-w-1200 mx-auto">
                        <?php if (count($cursos_activos) > 0): ?>
                            <?php foreach ($cursos_activos as $clase): ?>
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="card shadow-sm border-0 h-100 tarjeta-hover borde-vino">
                                        <div class="card-body d-flex flex-column text-center p-4">
                                            <i class="bi bi-journal-text icono-gigante mb-2"></i>
                                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($clase['nombre_materia']); ?></h5>
                                            <p class="text-muted mb-4"><span class="badge bg-secondary me-1"><?php echo htmlspecialchars($clase['clave_materia']); ?></span> Grupo: <strong><?php echo htmlspecialchars($clase['nombre_grupo']); ?></strong></p>
                                            <div class="mt-auto d-grid">
                                                <a href="calificaciones.php?horario=<?php echo $clase['id_horario']; ?>" class="btn text-white fw-semibold" style="background-color: var(--rojo-vino);">
                                                    <i class="bi bi-pencil-square me-1"></i> Calificar Grupo
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12"><div class="alert alert-light border text-center py-4 shadow-sm"><h6 class="mb-0 text-muted">No hay grupos activos en este momento.</h6></div></div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($rol_actual == 'Alumno'): ?>
                    
                    <div class="row g-4 justify-content-center mb-5">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="calificaciones.php" class="text-decoration-none"><div class="card shadow-sm border-0 text-center p-3 tarjeta-hover" style="border-top: 4px solid var(--rojo-vino);"><i class="bi bi-journal-text fs-1" style="color: var(--rojo-vino);"></i><h6 class="fw-bold text-dark mt-2 mb-0">Mi Boleta</h6></div></a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="kardex.php" class="text-decoration-none"><div class="card shadow-sm border-0 text-center p-3 tarjeta-hover" style="border-top: 4px solid #6c757d;"><i class="bi bi-archive-fill fs-1 text-secondary"></i><h6 class="fw-bold text-dark mt-2 mb-0">Mi Kardex</h6></div></a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="finanzas.php" class="text-decoration-none"><div class="card shadow-sm border-0 text-center p-3 tarjeta-hover" style="border-top: 4px solid var(--rojo-vino);"><i class="bi bi-cash-coin fs-1" style="color: var(--rojo-vino);"></i><h6 class="fw-bold text-dark mt-2 mb-0">Finanzas</h6></div></a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="servicio_social.php" class="text-decoration-none"><div class="card shadow-sm border-0 text-center p-3 tarjeta-hover" style="border-top: 4px solid var(--rojo-vino);"><i class="bi bi-building-check fs-1" style="color: var(--rojo-vino);"></i><h6 class="fw-bold text-dark mt-2 mb-0">Servicio Social</h6></div></a>
                        </div>
                    </div>

                    <hr class="opacity-25 mb-4">
                    <h4 class="fw-bold mb-4 text-center text-dark">Materias en Curso</h4>
                    <div class="row g-4 justify-content-center mb-5">
                        <?php if (count($cursos_activos) > 0): ?>
                            <?php foreach ($cursos_activos as $clase): ?>
                                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                                    <div class="card shadow-sm border-0 h-100 tarjeta-hover borde-vino">
                                        <div class="card-body text-center p-4">
                                            <span class="badge bg-secondary mb-3 fs-6">Grupo <?php echo htmlspecialchars($clase['nombre_grupo']); ?></span>
                                            <h5 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($clase['nombre_materia']); ?></h5>
                                            <div class="p-2 rounded bg-light">
                                                <small class="text-muted d-block mb-1"><i class="bi bi-person-workspace me-1"></i> Docente:</small>
                                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($clase['profe_nombre'] . ' ' . $clase['profe_apellido']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12"><div class="alert alert-light border text-center py-4 shadow-sm"><h6 class="mb-0 text-muted">No estás inscrito en ninguna materia actualmente.</h6></div></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>