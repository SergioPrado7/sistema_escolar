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

$tiene_foto = false;
$foto_header = "";

try {
    $stmt_foto = $db->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = :id");
    $stmt_foto->execute([':id' => $id_usuario_actual]);
    $foto_db = $stmt_foto->fetch(PDO::FETCH_ASSOC);

    if ($foto_db && !empty($foto_db['foto_perfil']) && $foto_db['foto_perfil'] != 'default.png') {
        $tiene_foto = true;
        $foto_header = '../assets/perfiles/' . $foto_db['foto_perfil'];
    }
} catch (Exception $e) {
}

$cursos_activos = [];

if ($rol_actual == 'Profesor') {
    $query = "SELECT h.id_horario, m.nombre_materia, m.clave_materia, g.nombre_grupo, 
                     IFNULL((SELECT MAX(finalizado) FROM carga_academica WHERE id_horario = h.id_horario), 0) as curso_finalizado
              FROM horarios h 
              JOIN materias m ON h.id_materia = m.id_materia 
              JOIN grupos g ON h.id_grupo = g.id_grupo 
              WHERE h.id_profesor = :id_profesor
              GROUP BY g.id_grupo";
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
              WHERE ca.id_alumno = :id_alumno AND ca.finalizado = 0
              GROUP BY g.id_grupo";
    $stmt = $db->prepare($query);
    $stmt->execute([':id_alumno' => $id_usuario_actual]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $curso) {
        $cursos_activos[] = $curso;
    }
}

$mi_horario = [];
$alumno_info = null;

try {
    if ($rol_actual == 'Administrador') {
        if (isset($_GET['matricula']) && !empty(trim($_GET['matricula']))) {
            $matricula_buscada = trim($_GET['matricula']);

            $query = "SELECT DISTINCT h_all.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                             h_all.hora_inicio, h_all.hora_fin, h_all.dia_semana, per.nombre_periodo, h_all.cupo_maximo
                      FROM carga_academica ca 
                      JOIN horarios h_inscrito ON ca.id_horario = h_inscrito.id_horario 
                      JOIN horarios h_all ON h_inscrito.id_materia = h_all.id_materia AND h_inscrito.id_grupo = h_all.id_grupo
                      JOIN materias m ON h_all.id_materia = m.id_materia 
                      JOIN grupos g ON h_all.id_grupo = g.id_grupo 
                      JOIN usuarios u_alumno ON ca.id_alumno = u_alumno.id_usuario
                      JOIN usuarios u_profe ON h_all.id_profesor = u_profe.id_usuario 
                      JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                      LEFT JOIN periodos per ON h_all.id_periodo = per.id_periodo
                      WHERE u_alumno.matricula = :matricula AND ca.finalizado = 0
                      ORDER BY h_all.hora_inicio ASC";
            $stmt = $db->prepare($query);
            $stmt->execute([':matricula' => $matricula_buscada]);
            $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt_alumno = $db->prepare("SELECT p.nombre, p.apellido_paterno, u.rol FROM usuarios u JOIN personas p ON u.id_usuario = p.id_usuario WHERE u.matricula = :matricula");
            $stmt_alumno->execute([':matricula' => $matricula_buscada]);
            $alumno_info = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
        } else {
            $mi_horario = [];
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
                  WHERE h.id_profesor = :id_usuario AND IFNULL((SELECT MAX(finalizado) FROM carga_academica WHERE id_horario = h.id_horario), 0) = 0
                  ORDER BY h.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario_actual]);
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($rol_actual == 'Alumno') {
        $query = "SELECT DISTINCT h_all.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno, 
                         h_all.hora_inicio, h_all.hora_fin, h_all.dia_semana, per.nombre_periodo, h_all.cupo_maximo
                  FROM carga_academica ca 
                  JOIN horarios h_inscrito ON ca.id_horario = h_inscrito.id_horario 
                  JOIN horarios h_all ON h_inscrito.id_materia = h_all.id_materia AND h_inscrito.id_grupo = h_all.id_grupo
                  JOIN materias m ON h_all.id_materia = m.id_materia 
                  JOIN grupos g ON h_all.id_grupo = g.id_grupo 
                  JOIN usuarios u_profe ON h_all.id_profesor = u_profe.id_usuario 
                  JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                  LEFT JOIN periodos per ON h_all.id_periodo = per.id_periodo
                  WHERE ca.id_alumno = :id_usuario AND ca.finalizado = 0
                  ORDER BY h_all.hora_inicio ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario_actual]);
        $mi_horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error al cargar el horario: " . $e->getMessage();
}

$nombre_propietario_horario = "";
if ($rol_actual == 'Administrador' && isset($alumno_info) && $alumno_info) {
    $nombre_propietario_horario = $alumno_info['nombre'] . ' ' . $alumno_info['apellido_paterno'];
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

        .foto-mini-header {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--rojo-vino);
        }

        @media print {
            @page {
                size: letter landscape;
                margin: 5mm;
            }

            body {
                background-color: white !important;
                font-size: 8pt !important;
            }

            .no-imprimir,
            .d-print-none {
                display: none !important;
            }

            .main_contenido {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            table {
                border-collapse: collapse !important;
                width: 100% !important;
                table-layout: fixed !important;
                font-size: 7.5pt !important;
            }

            thead {
                display: table-header-group !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            th,
            td {
                border: 1px solid #000 !important;
                color: #000 !important;
                padding: 2px !important;
                text-align: center !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
            }

            .bg-vino,
            .bg-vino * {
                background-color: var(--rojo-vino) !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge.bg-light {
                background-color: white !important;
                color: black !important;
                border: 1px solid black !important;
                font-size: 6pt !important;
                padding: 1px 3px !important;
                display: inline-block !important;
            }

            td[style*="height"] {
                height: auto !important;
                min-height: 30px !important;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">

        <nav class="sidebar d-none d-md-flex no-imprimir d-print-none">
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

        <nav class="navbar navbar-dark d-md-none p-3 w-100 no-imprimir d-print-none" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
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
                        <h1 style="color: var(--rojo-vino); font-weight: bold;" class="no-imprimir d-print-none">
                            <i class="bi bi-calendar3 me-2"></i>
                            <?php echo ($rol_actual == 'Administrador') ? 'Horarios' : 'Mi Horario'; ?>
                        </h1>
                        <div class="d-none d-print-block text-center mb-4">
                            <h3 class="fw-bold mb-1" style="color: var(--rojo-vino);">HORARIO DE CLASES</h3>
                            <h4 class="fw-bold text-dark text-uppercase"><?php echo htmlspecialchars($nombre_propietario_horario); ?></h4>
                        </div>
                    </div>

                    <button onclick="window.print()" class="btn text-white fw-bold mt-3 mt-md-0 no-imprimir d-print-none shadow-sm" style="background-color: var(--rojo-vino);">
                        <i class="bi bi-printer-fill me-2"></i> Imprimir Horario
                    </button>
                </div>

                <?php if ($rol_actual == 'Administrador'): ?>
                    <form action="" method="GET" class="mb-4 mt-2 no-imprimir d-print-none">
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
                <?php endif; ?>

                <?php if (isset($alumno_info) && $alumno_info): ?>
                    <?php if (isset($alumno_info['rol']) && $alumno_info['rol'] == 'Administrador'): ?>
                        <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center mb-4 no-imprimir d-print-none" style="border-left: 5px solid #ffc107 !important;">
                            <i class="bi bi-shield-lock-fill fs-3 me-3 text-warning"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Perfil de Administrador</h6>
                                <span class="text-dark">El usuario <strong><?php echo htmlspecialchars($alumno_info['nombre'] . ' ' . $alumno_info['apellido_paterno']); ?></strong> es un Administrador. Los administradores no tienen horario asignado.</span>
                            </div>
                        </div>
                    <?php elseif (isset($alumno_info['rol']) && $alumno_info['rol'] == 'Profesor'): ?>
                        <div class="alert alert-info shadow-sm border-0 d-flex align-items-center mb-4 no-imprimir d-print-none" style="border-left: 5px solid #0dcaf0 !important;">
                            <i class="bi bi-person-video3 fs-3 me-3 text-info"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Perfil de Profesor</h6>
                                <span class="text-dark"><strong><?php echo htmlspecialchars($alumno_info['nombre'] . ' ' . $alumno_info['apellido_paterno']); ?></strong> es un Profesor. Sus grupos se administran desde la Gestión Académica.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert shadow-sm border-0 d-flex align-items-center mb-4 no-imprimir d-print-none" style="background-color: #e9ecef; border-left: 5px solid var(--rojo-vino) !important;">
                            <i class="bi bi-person-bounding-box fs-3 me-3" style="color: var(--rojo-vino);"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Horario de Alumno:</h6>
                                <span class="text-dark fs-5"><?php echo htmlspecialchars($alumno_info['nombre'] . ' ' . $alumno_info['apellido_paterno']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php elseif (isset($_GET['matricula']) && !empty($_GET['matricula']) && !$alumno_info): ?>
                    <div class="alert alert-danger shadow-sm mb-4 no-imprimir d-print-none">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> No se encontró a nadie en el sistema con esta matrícula.
                    </div>
                <?php elseif (!empty($nombre_propietario_horario) && $nombre_propietario_horario != "Horario General de Grupos"): ?>
                    <div class="alert shadow-sm border-0 d-flex align-items-center mb-4 no-imprimir d-print-none" style="background-color: #e9ecef; border-left: 5px solid var(--rojo-vino) !important;">
                        <i class="bi <?php echo ($rol_actual == 'Profesor') ? 'bi-person-video3' : 'bi-person-bounding-box'; ?> fs-3 me-3" style="color: var(--rojo-vino);"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Propietario del Horario:</h6>
                            <span class="text-dark fs-5"><?php echo htmlspecialchars($nombre_propietario_horario); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

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

                            // HORAS EN FORMATO 24 HORAS
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
                                <table class="table table-bordered text-center align-middle mb-0" style="table-layout: fixed; width: 100%; min-width: 1000px; font-size: 0.75rem;">
                                    <thead>
                                        <tr style="background-color: var(--rojo-vino);">
                                            <th class="py-1 bg-vino text-white" style="width: 10%; background-color: var(--rojo-vino); font-size: 0.8rem;">Hora</th>
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
                                                                        <i class="bi bi-person-video3 no-imprimir d-print-none"></i> <?php echo htmlspecialchars($clase_info['profe_nombre']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <span class="badge bg-light text-dark mt-1" style="font-size: 0.55rem; border-radius: 3px;">Gpo <?php echo htmlspecialchars($clase_info['nombre_grupo']); ?></span>
                                                            </div>
                                                        </td>
                                                    <?php else: ?>
                                                        <td class="border bg-white" style="height: 35px; padding: 0;"></td>
                                                <?php endif;
                                                endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted border rounded bg-light m-3 no-imprimir d-print-none">
                                    <?php if ($rol_actual == 'Administrador' && empty($_GET['matricula'])): ?>
                                        <i class="bi bi-search fs-1 d-block mb-3"></i>
                                        Utiliza el buscador de arriba para consultar el horario de un alumno.
                                    <?php else: ?>
                                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                        Aún no hay clases registradas en este horario.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>