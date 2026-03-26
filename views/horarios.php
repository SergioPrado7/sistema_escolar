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

try {
    if ($rol_actual == 'Administrador') {
        $mi_horario = []; 
        $matricula_buscada = $_GET['matricula'] ?? '';
        
        $estado_busqueda = ''; 
        $nombre_usuario = '';

        if (!empty($matricula_buscada)) {
            
            $stmt_usuario = $db->prepare("SELECT u.rol, p.nombre, p.apellido_paterno FROM usuarios u JOIN personas p ON u.id_usuario = p.id_usuario WHERE u.matricula = :matricula");
            $stmt_usuario->execute([':matricula' => $matricula_buscada]);
            $datos_usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

            if ($datos_usuario) {
                $nombre_usuario = $datos_usuario['nombre'] . ' ' . $datos_usuario['apellido_paterno'];
                $estado_busqueda = $datos_usuario['rol']; 
                
                if ($estado_busqueda == 'Alumno') {
                    $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                                     h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
                              FROM carga_academica ca 
                              JOIN horarios h ON ca.id_horario = h.id_horario 
                              JOIN materias m ON h.id_materia = m.id_materia 
                              JOIN grupos g ON h.id_grupo = g.id_grupo 
                              JOIN usuarios u_profe ON h.id_profesor = u_profe.id_usuario 
                              JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                              JOIN usuarios u_alumno ON ca.id_alumno = u_alumno.id_usuario
                              LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                              WHERE u_alumno.matricula = :matricula 
                              AND ca.finalizado = 0 
                              ORDER BY h.hora_inicio ASC";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':matricula' => $matricula_buscada]);
                    $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $estado_busqueda = 'No_Existe';
            }
        }
    }
    elseif ($rol_actual == 'Profesor') {
        $query = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h.hora_inicio, h.hora_fin, h.dia_semana, per.nombre_periodo, h.cupo_maximo
                  FROM horarios h 
                  JOIN materias m ON h.id_materia = m.id_materia 
                  JOIN grupos g ON h.id_grupo = g.id_grupo 
                  JOIN usuarios u ON h.id_profesor = u.id_usuario 
                  JOIN personas p ON u.id_usuario = p.id_usuario 
                  LEFT JOIN periodos per ON h.id_periodo = per.id_periodo
                  WHERE h.id_profesor = :id_usuario
                  AND (
                      (SELECT COUNT(*) FROM carga_academica ca INNER JOIN horarios h2 ON ca.id_horario = h2.id_horario WHERE h2.id_grupo = h.id_grupo) = 0
                      OR 
                      (SELECT COUNT(*) FROM carga_academica ca INNER JOIN horarios h2 ON ca.id_horario = h2.id_horario WHERE h2.id_grupo = h.id_grupo AND ca.finalizado = 0) > 0
                  )
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
                  AND ca.finalizado = 0 
                  ORDER BY h.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario_actual]);
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error al cargar el horario: " . $e->getMessage();
}

$nombre_propietario_horario = "";
if ($rol_actual == 'Administrador') {
    if (!empty($nombre_usuario) && $estado_busqueda == 'Alumno') {
        $nombre_propietario_horario = $nombre_usuario;
    } else {
        $nombre_propietario_horario = "Buscador de Horarios";
    }
} elseif ($rol_actual == 'Profesor' || $rol_actual == 'Alumno') {
    $stmt_mi_nombre = $db->prepare("SELECT p.nombre, p.apellido_paterno FROM usuarios u JOIN personas p ON u.id_usuario = p.id_usuario WHERE u.id_usuario = :id");
    $stmt_mi_nombre->execute([':id' => $id_usuario_actual]);
    $mi_info = $stmt_mi_nombre->fetch(PDO::FETCH_ASSOC);
    if ($mi_info) {
        $nombre_propietario_horario = $mi_info['nombre'] . ' ' . $mi_info['apellido_paterno'];
    }
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
        .borde-vino {
            border-left: 5px solid var(--rojo-vino) !important;
        }

        .main_contenido {
            margin-left: 0 !important;
            width: 100% !important;
        }

        @media print {
            @page {
                size: letter landscape;
                margin: 5mm; 
            }
            body { background-color: white !important; font-size: 8pt !important; }
            .no-imprimir { display: none !important; }
            .main_contenido { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
            table { border-collapse: collapse !important; width: 100% !important; table-layout: fixed !important; font-size: 7.5pt !important; }
            thead { display: table-header-group !important; }
            tr { page-break-inside: avoid !important; break-inside: avoid !important; }
            th, td { border: 1px solid #000 !important; color: #000 !important; padding: 2px !important; text-align: center !important; vertical-align: middle !important; word-wrap: break-word !important; }
            .bg-vino, .bg-vino * { background-color: var(--rojo-vino) !important; color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .badge.bg-light { background-color: white !important; color: black !important; border: 1px solid black !important; font-size: 6pt !important; padding: 1px 3px !important; display: inline-block !important; }
            td[style*="height"] { height: auto !important; min-height: 30px !important; }
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
                <a href="horarios.php" class="item active">Horarios</a>
                <a href="calificaciones.php" class="item">Calificaciones</a>
                <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                    <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                    <a href="gestion_academica.php" class="item">Gestión Académica</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                    <a href="servicio_social.php" class="item">Servicio Social</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?>
                    <a href="kardex.php" class="item">Kardex</a>
                <?php endif; ?>
            </div>
        </nav>

        <nav class="navbar navbar-dark d-md-none p-3 w-100 no-imprimir" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="text-white fw-bold">Horarios</span>
                <div class="collapse navbar-collapse" id="menuMovil">
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="dashboard.php" class="item">Panel Principal</a>
                        <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                            <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                        <?php endif; ?>
                        <a href="horarios.php" class="item active">Horarios</a>
                        <a href="calificaciones.php" class="item">Calificaciones</a>
                        <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                            <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                            <a href="gestion_academica.php" class="item">Gestión Académica</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?>
                            <a href="servicio_social.php" class="item">Servicio Social</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?>
                            <a href="kardex.php" class="item">Kardex</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main_contenido flex-grow-1 min-vh-100">
            <div class="p-4">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <div>
                        <h1 style="color: var(--rojo-vino); font-weight: bold;" class="no-imprimir">
                            <i class="bi bi-calendar3 me-2"></i>
                            <?php echo ($rol_actual == 'Administrador') ? 'Horarios' : 'Mi Horario'; ?>
                        </h1>
                        <div class="d-none d-print-block text-center mb-4">
                            <h3 class="fw-bold mb-1" style="color: var(--rojo-vino);">HORARIO DE CLASES</h3>
                            <h4 class="fw-bold text-dark text-uppercase"><?php echo htmlspecialchars($nombre_propietario_horario); ?></h4>
                        </div>
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
                                    <?php if (isset($_GET['matricula']) && !empty($_GET['matricula'])): ?>
                                        <a href="horarios.php" class="btn btn-secondary">Limpiar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (isset($_GET['matricula']) && !empty($_GET['matricula'])): ?>
                        <?php if ($estado_busqueda == 'Alumno'): ?>
                            <div class="alert alert-success shadow-sm mb-4" style="border-left: 5px solid #198754;">
                                <i class="bi bi-person-check-fill me-2"></i> Mostrando horario en curso del alumno: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                            </div>
                        <?php elseif ($estado_busqueda == 'Profesor'): ?>
                            <div class="alert alert-info shadow-sm mb-4" style="border-left: 5px solid #0dcaf0;">
                                <i class="bi bi-person-badge-fill me-2"></i> Atención: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> tiene rol de <strong>Profesor</strong>. Los docentes visualizan su horario en su propio panel.
                            </div>
                        <?php elseif ($estado_busqueda == 'Administrador'): ?>
                            <div class="alert alert-warning shadow-sm mb-4" style="border-left: 5px solid #ffc107;">
                                <i class="bi bi-shield-lock-fill me-2"></i> Atención: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> es un <strong>Administrador</strong> del sistema.
                            </div>
                        <?php elseif ($estado_busqueda == 'No_Existe'): ?>
                            <div class="alert alert-danger shadow-sm mb-4" style="border-left: 5px solid #dc3545;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> No se encontró a nadie en el sistema con la matrícula <strong><?php echo htmlspecialchars($_GET['matricula']); ?></strong>.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php 
                $mostrar_tabla = false;
                if ($rol_actual == 'Profesor' || $rol_actual == 'Alumno') { $mostrar_tabla = true; }
                if ($rol_actual == 'Administrador' && isset($estado_busqueda) && $estado_busqueda == 'Alumno') { $mostrar_tabla = true; }
                ?>

                <?php if ($mostrar_tabla): ?>
                    <div class="card shadow-sm border-0 borde-vino">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <?php
                                $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                $tiene_sabado = false;
                                
                                foreach ($mi_horario as $clase) {
                                    if (trim($clase['dia_semana']) == 'Sábado') {
                                        $tiene_sabado = true;
                                        break;
                                    }
                                }
                                
                                if (!$tiene_sabado) {
                                    $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                                }

                                $bloques_tiempo = [
                                    '07:00:00' => '07:00 - 07:50',
                                    '07:50:00' => '07:50 - 08:40',
                                    '08:40:00' => '08:40 - 09:30',
                                    '09:30:00' => '09:30 - 10:20',
                                    '10:20:00' => '10:20 - 11:10',
                                    '11:10:00' => '11:10 - 12:00',
                                    '12:00:00' => '12:00 - 12:50',
                                    '12:50:00' => '12:50 - 13:40',
                                    '13:40:00' => '13:40 - 14:30',
                                    '14:30:00' => '14:30 - 15:20',
                                    '15:20:00' => '15:20 - 16:10',
                                    '16:10:00' => '16:10 - 17:00'
                                ];
                              
                                $matriz_horario = [];
                                foreach ($bloques_tiempo as $hora_idx => $etiqueta) {
                                    foreach ($dias_semana as $dia) {
                                        $matriz_horario[$hora_idx][$dia] = false; 
                                    }
                                }

                                foreach ($mi_horario as $clase) {
                                    $dia = trim($clase['dia_semana']);
                                    $h_inicio = date('H:i:s', strtotime($clase['hora_inicio']));
                                    $h_fin = date('H:i:s', strtotime($clase['hora_fin']));
                                    
                                    $minutos = (strtotime($h_fin) - strtotime($h_inicio)) / 60;
                                    $rowspan = round($minutos / 50);
                                    if ($rowspan < 1) $rowspan = 1;

                                    if (array_key_exists($h_inicio, $matriz_horario) && in_array($dia, $dias_semana)) {
                                        $matriz_horario[$h_inicio][$dia] = [
                                            'clase' => $clase,
                                            'rowspan' => $rowspan
                                        ];
                                        
                                        $hora_calc = strtotime($h_inicio);
                                        for ($i = 1; $i < $rowspan; $i++) {
                                            $hora_calc += (50 * 60);
                                            $hora_siguiente = date('H:i:s', $hora_calc);
                                            if (array_key_exists($hora_siguiente, $matriz_horario)) {
                                                $matriz_horario[$hora_siguiente][$dia] = 'skip';
                                            }
                                        }
                                    }
                                }
                                ?>

                                <?php if (count($mi_horario) > 0): ?>
                                    <table class="table table-bordered text-center align-middle mb-0" style="table-layout: fixed; width: 100%; font-size: 0.75rem;">
                                        <thead>
                                            <tr style="background-color: var(--rojo-vino);">
                                                <th class="py-1 bg-vino text-white" style="width: 12%; background-color: var(--rojo-vino); font-size: 0.8rem;">Hora</th>
                                                <?php foreach ($dias_semana as $dia): ?>
                                                    <th class="py-1 bg-vino text-white" style="background-color: var(--rojo-vino); font-size: 0.8rem;"><?php echo $dia; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bloques_tiempo as $hora_idx => $etiqueta): ?>
                                                <tr>
                                                    <td class="fw-bold bg-light text-dark border-end" style="font-size: 0.7rem; padding: 2px;">
                                                        <?php echo $etiqueta; ?>
                                                    </td>

                                                    <?php foreach ($dias_semana as $dia): 
                                                        $celda = $matriz_horario[$hora_idx][$dia];
                                                        
                                                        if ($celda === 'skip') {
                                                            continue;
                                                        }

                                                        if ($celda !== false && is_array($celda)): 
                                                            $clase_info = $celda['clase'];
                                                    ?>
                                                            <td class="p-1 border align-middle" rowspan="<?php echo $celda['rowspan']; ?>" style="background-color: var(--rojo-vino); box-shadow: inset 0 0 0 1px white;">
                                                                <div class="text-white d-flex flex-column justify-content-center align-items-center h-100 w-100">
                                                                    <strong class="text-uppercase mb-1" style="font-size: 0.65rem; line-height: 1.1;">
                                                                        <?php echo htmlspecialchars($clase_info['nombre_materia']); ?>
                                                                    </strong>
                                                                    <?php if ($rol_actual == 'Alumno' || $rol_actual == 'Administrador'): ?>
                                                                        <span class="mb-1" style="font-size: 0.6rem;">
                                                                            <i class="bi bi-person-video3 no-imprimir"></i> <?php echo htmlspecialchars($clase_info['profe_nombre']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <span class="badge bg-light text-dark mt-1" style="font-size: 0.55rem; border-radius: 3px;">Gpo <?php echo htmlspecialchars($clase_info['nombre_grupo']); ?></span>
                                                                </div>
                                                            </td>
                                                    <?php else: ?>
                                                            <td class="border bg-white" style="height: 35px; padding: 0;"></td>
                                                    <?php endif; endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted border rounded bg-light m-3 no-imprimir">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                        Aún no hay clases registradas en el horario.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>