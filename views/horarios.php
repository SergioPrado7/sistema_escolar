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
$alumno_info = null;

// ==============================================================
// LÓGICA DE HORARIOS SEGÚN EL ROL
// ==============================================================
try {
    if ($rol_actual == 'Administrador') {
        // Si el administrador buscó una matrícula
        if (isset($_GET['matricula']) && !empty(trim($_GET['matricula']))) {
            $matricula_buscada = trim($_GET['matricula']);
            
            // Traemos el horario de ESE alumno en específico
            $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                             h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
                      FROM carga_academica ca 
                      JOIN horarios h ON ca.id_horario = h.id_horario 
                      JOIN materias m ON h.id_materia = m.id_materia 
                      JOIN grupos g ON h.id_grupo = g.id_grupo 
                      JOIN usuarios u_alumno ON ca.id_alumno = u_alumno.id_usuario
                      JOIN usuarios u_profe ON h.id_profesor = u_profe.id_usuario 
                      JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                      LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                      WHERE u_alumno.matricula = :matricula
                      ORDER BY h.hora_inicio ASC";
            $stmt = $db->prepare($query);
            $stmt->execute([':matricula' => $matricula_buscada]);
            $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Traemos el nombre del alumno para mostrarlo bonito
            $stmt_alumno = $db->prepare("SELECT p.nombre, p.apellido_paterno FROM usuarios u JOIN personas p ON u.id_usuario = p.id_usuario WHERE u.matricula = :matricula");
            $stmt_alumno->execute([':matricula' => $matricula_buscada]);
            $alumno_info = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
            
        } else {
            // Si no ha buscado nada, mostramos el Horario Maestro general
            $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                             h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
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
        }

    } elseif ($rol_actual == 'Profesor') {
        $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
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
                         h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
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
            .bg-vino { background-color: #ddd !important; color: #000 !important; }
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
                <a href="horarios.php" class="item active">Horarios</a> 
                <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
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
                        <a href="horarios.php" class="item active">Horarios</a> 
                        <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
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

                <?php if ($rol_actual == 'Administrador'): ?>
                <form action="" method="GET" class="mb-4 mt-2 no-imprimir">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <label class="form-label fw-bold text-muted small">Consultar horario por alumno:</label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="matricula" class="form-control border-start-0" placeholder="Ej. 221000135" value="<?php echo isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : ''; ?>">
                                <button type="submit" class="btn text-white px-4" style="background-color: var(--rojo-vino);">Buscar</button>
                                <?php if(isset($_GET['matricula']) && !empty($_GET['matricula'])): ?>
                                    <a href="horarios.php" class="btn btn-secondary">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php if(isset($alumno_info) && $alumno_info): ?>
                    <div class="alert shadow-sm border-0 d-flex align-items-center mb-4" style="background-color: #e9ecef; border-left: 5px solid var(--rojo-vino) !important;">
                        <i class="bi bi-person-bounding-box fs-3 me-3" style="color: var(--rojo-vino);"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Mostrando el horario de:</h6>
                            <span class="text-dark fs-5"><?php echo htmlspecialchars($alumno_info['nombre'] . ' ' . $alumno_info['apellido_paterno']); ?></span>
                        </div>
                    </div>
                <?php elseif(isset($_GET['matricula']) && !empty($_GET['matricula']) && !$alumno_info): ?>
                    <div class="alert alert-danger shadow-sm mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> No se encontró ningún alumno con esa matrícula o aún no tiene materias asignadas.
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                <div class="card shadow-sm border-0 borde-vino">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <?php 
                            // 1. Definimos los días de la semana fijos para las columnas
                            $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                            // 2. Extraemos las horas únicas para hacer las filas
                            $horas_unicas = [];
                            foreach ($mi_horario as $clase) {
                                $rango = substr($clase['hora_inicio'], 0, 5) . ' - ' . substr($clase['hora_fin'], 0, 5);
                                if (!in_array($rango, $horas_unicas)) {
                                    $horas_unicas[] = $rango;
                                }
                            }
                            // Ordenamos las horas de más temprano a más tarde
                            sort($horas_unicas);
                            ?>

                            <?php if (count($mi_horario) > 0): ?>
                                <table class="table table-bordered text-center align-middle mb-0 table-sm" style="min-width: 800px;">
                                    <thead>
                                        <tr class="text-white" style="background-color: var(--rojo-vino);">
                                            <th class="py-3 bg-vino" style="width: 10%; background-color: var(--rojo-vino);">Hora</th>
                                            <?php foreach($dias_semana as $dia): ?>
                                                <th class="py-3 bg-vino" style="width: 15%; background-color: var(--rojo-vino);"><?php echo $dia; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($horas_unicas as $hora): ?>
                                            <tr>
                                                <td class="fw-bold bg-light text-muted border-end" style="font-size: 0.9rem;">
                                                    <i class="bi bi-clock me-1 no-imprimir"></i><br>
                                                    <?php echo $hora; ?>
                                                </td>

                                                <?php foreach($dias_semana as $dia): 
                                                    // Buscamos si hay una clase que coincida con esta HORA y este DÍA
                                                    $clase_encontrada = null;
                                                    foreach($mi_horario as $clase) {
                                                        $rango_clase = substr($clase['hora_inicio'], 0, 5) . ' - ' . substr($clase['hora_fin'], 0, 5);
                                                        if ($clase['dia_semana'] == $dia && $rango_clase == $hora) {
                                                            $clase_encontrada = $clase;
                                                            break; // Si la encontramos, dejamos de buscar
                                                        }
                                                    }
                                                ?>
                                                
                                                <td class="p-2 border" style="height: 100px;">
                                                    <?php if($clase_encontrada): ?>
                                                        <div class="h-100 p-2 rounded shadow-sm text-white d-flex flex-column justify-content-center bg-vino" style="background-color: var(--rojo-vino); font-size: 0.8rem;">
                                                            <strong class="text-uppercase mb-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                                                <?php echo htmlspecialchars($clase_encontrada['nombre_materia']); ?>
                                                            </strong>
                                                            <?php if ($rol_actual == 'Alumno' || $rol_actual == 'Administrador'): ?>
                                                                <span class="mb-1"><i class="bi bi-person-video3"></i> <?php echo htmlspecialchars($clase_encontrada['profe_nombre']); ?></span>
                                                            <?php endif; ?>
                                                            <div>
                                                                <span class="badge bg-light text-dark shadow-sm">Grupo <?php echo htmlspecialchars($clase_encontrada['nombre_grupo']); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted opacity-25">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted border rounded bg-light m-3">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                    <?php if(isset($_GET['matricula']) && !empty($_GET['matricula'])): ?>
                                        Este alumno no tiene clases registradas.
                                    <?php else: ?>
                                        Aún no hay clases registradas en el horario.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>