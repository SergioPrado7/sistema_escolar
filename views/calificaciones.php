<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();
$id_usuario_actual = $_SESSION['id_usuario'];
$rol_actual = $_SESSION['rol'];

// ==============================================================
// 1. LÓGICA PARA PROFESORES Y ADMINISTRADORES
// ==============================================================
if ($rol_actual == 'Profesor' || $rol_actual == 'Administrador') {
    
    // GUARDAR O FINALIZAR CALIFICACIONES
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['guardar_calificaciones']) || isset($_POST['finalizar_curso']))) {
        $id_horario_actual = $_POST['id_horario_seleccionado'];
        $es_finalizar = isset($_POST['finalizar_curso']) ? 1 : 0;
        
        $u1 = $_POST['u1'] ?? [];
        $u2 = $_POST['u2'] ?? [];
        $u3 = $_POST['u3'] ?? [];
        $u4 = $_POST['u4'] ?? [];
        $u5 = $_POST['u5'] ?? [];
        $u6 = $_POST['u6'] ?? [];
        $ids_cargas = $_POST['id_cargas'] ?? [];

        try {
            $db->beginTransaction();
            
            // Actualizamos la base de datos
            $query_update = "UPDATE carga_academica SET u1=?, u2=?, u3=?, u4=?, u5=?, u6=?, calificacion=?, finalizado=IF(finalizado=1 AND ?=0, 1, ?) WHERE id_carga=?";
            $stmt_update = $db->prepare($query_update);

            foreach ($ids_cargas as $id_carga) {
                // Convertir a null si está vacío
                $v1 = ($u1[$id_carga] !== '') ? floatval($u1[$id_carga]) : null;
                $v2 = ($u2[$id_carga] !== '') ? floatval($u2[$id_carga]) : null;
                $v3 = ($u3[$id_carga] !== '') ? floatval($u3[$id_carga]) : null;
                $v4 = ($u4[$id_carga] !== '') ? floatval($u4[$id_carga]) : null;
                $v5 = ($u5[$id_carga] !== '') ? floatval($u5[$id_carga]) : null;
                $v6 = ($u6[$id_carga] !== '') ? floatval($u6[$id_carga]) : null;
                
                // Calcular promedio ignorando nulos
                $suma = 0; $count = 0;
                $vals = [$v1, $v2, $v3, $v4, $v5, $v6];
                foreach($vals as $v) { 
                    if($v !== null) { $suma += $v; $count++; } 
                }
                $promedio = ($count > 0) ? round($suma / $count, 1) : null;
                
                $stmt_update->execute([$v1, $v2, $v3, $v4, $v5, $v6, $promedio, $es_finalizar, $es_finalizar, $id_carga]);
            }
            
            $db->commit();
            header("Location: calificaciones.php?horario=" . $id_horario_actual . "&exito=1");
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            die("Error al guardar calificaciones: " . $e->getMessage());
        }
    }

    // OBTENER GRUPOS (Sabiendo si ya fueron finalizados)
    $query_mis_grupos = "SELECT h.id_horario, m.nombre_materia, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno,
                         IFNULL((SELECT MAX(finalizado) FROM carga_academica WHERE id_horario = h.id_horario), 0) as curso_finalizado
                         FROM horarios h 
                         INNER JOIN materias m ON h.id_materia = m.id_materia 
                         INNER JOIN grupos g ON h.id_grupo = g.id_grupo 
                         INNER JOIN usuarios u ON h.id_profesor = u.id_usuario 
                         INNER JOIN personas p ON u.id_usuario = p.id_usuario";
                         
    if ($rol_actual != 'Administrador') {
        $query_mis_grupos .= " WHERE h.id_profesor = :id_profesor";
        $stmt_grupos = $db->prepare($query_mis_grupos);
        $stmt_grupos->execute([':id_profesor' => $id_usuario_actual]);
    } else {
        $stmt_grupos = $db->prepare($query_mis_grupos);
        $stmt_grupos->execute();
    }
    
    $mis_grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

    // OBTENER ALUMNOS DEL GRUPO SELECCIONADO
    $alumnos_grupo = [];
    $horario_seleccionado = isset($_GET['horario']) ? $_GET['horario'] : '';

    if (!empty($horario_seleccionado)) {
        $query_alumnos = "SELECT ca.*, u.matricula, p.nombre, p.apellido_paterno 
                          FROM carga_academica ca 
                          INNER JOIN usuarios u ON ca.id_alumno = u.id_usuario 
                          INNER JOIN personas p ON u.id_usuario = p.id_usuario 
                          WHERE ca.id_horario = :id_horario ORDER BY p.apellido_paterno ASC";
        $stmt_alumnos = $db->prepare($query_alumnos);
        $stmt_alumnos->execute([':id_horario' => $horario_seleccionado]);
        $alumnos_grupo = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ==============================================================
// 2. LÓGICA PARA EL ALUMNO (SOLO CURSOS NO FINALIZADOS)
// ==============================================================
$mis_calificaciones = [];
if ($rol_actual == 'Alumno') {
    $query_mis_calif = "SELECT ca.*, m.nombre_materia, m.creditos, g.nombre_grupo, p.nombre as profe_nombre, p.apellido_paterno as profe_apellido 
                        FROM carga_academica ca 
                        INNER JOIN horarios h ON ca.id_horario = h.id_horario 
                        INNER JOIN materias m ON h.id_materia = m.id_materia 
                        INNER JOIN usuarios u_profe ON h.id_profesor = u_profe.id_usuario 
                        INNER JOIN personas p ON u_profe.id_usuario = p.id_usuario 
                        INNER JOIN grupos g ON h.id_grupo = g.id_grupo 
                        WHERE ca.id_alumno = :id_alumno AND ca.finalizado = 0";
    $stmt_mis_calif = $db->prepare($query_mis_calif);
    $stmt_mis_calif->execute([':id_alumno' => $id_usuario_actual]);
    $mis_calificaciones = $stmt_mis_calif->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/calificacionesIcono.ico">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        .input-unidad { width: 55px; text-align: center; padding: 4px; font-weight: bold; border-radius: 4px; border: 1px solid #ccc; }
        .input-unidad:focus { border-color: var(--rojo-vino); outline: none; box-shadow: 0 0 3px rgba(128, 0, 32, 0.5); }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .final-score { font-size: 1.1em; font-weight: 900; }
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
            <?php if ($_SESSION['rol'] == 'Administrador'): ?><a href="gestion_usuarios.php" class="item">Gestión Usuarios</a><?php endif; ?>
            <a href="horarios.php" class="item">Horarios</a>
            <a href="calificaciones.php" class="item active">Calificaciones</a>
            <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?><a href="finanzas.php" class="item">Finanzas y Pagos</a><?php endif; ?>
            <?php if ($_SESSION['rol'] == 'Administrador'): ?><a href="gestion_academica.php" class="item">Carga Académica</a><?php endif; ?>
            <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?><a href="servicio_social.php" class="item">Servicio Social</a><?php endif; ?>
            <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?><a href="kardex.php" class="item">Kardex</a><?php endif; ?>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil"><span class="navbar-toggler-icon"></span></button>
            <span class="text-white fw-bold">Calificaciones</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                    <a href="gestion_usuarios.php" class="item">Gestión de Usuarios</a>
                    <?php endif; ?>
                    <a href="horarios.php" class="item">Horarios</a>
                    <a href="calificaciones.php" class="item active">Calificaciones</a>
                    <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                    <a href="servicio_social.php" class="item">Servicio Social</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?>
                    <a href="kardex.php" class="item">Kardex</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <?php if ($rol_actual == 'Profesor' || $rol_actual == 'Administrador'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: var(--rojo-vino); font-weight: bold;">Centro de Evaluaciones</h1>
                </div>

                <?php if (isset($_GET['exito'])): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> <strong>¡Excelente!</strong> Acciones guardadas correctamente. <button class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <form action="calificaciones.php" method="GET" class="row g-2 align-items-center">
                            <div class="col-12 col-md-8 col-lg-6">
                                <select name="horario" class="form-select shadow-sm" required onchange="this.form.submit()">
                                    <option value="">-- Elige la materia y grupo --</option>
                                    <?php foreach ($mis_grupos as $grupo): ?>
                                        <?php 
                                            // FILTRO MAGICO: Si el curso ya fue finalizado, lo saltamos y no lo mostramos en la lista
                                            if ($grupo['curso_finalizado'] == 0 || $horario_seleccionado == $grupo['id_horario']): 
                                        ?>
                                            <option value="<?php echo $grupo['id_horario']; ?>" <?php echo ($horario_seleccionado == $grupo['id_horario']) ? 'selected' : ''; ?>>
                                                <?php 
                                                    $texto_admin = ($rol_actual == 'Administrador') ? " (Prof. " . $grupo['profe_nombre'] . " " . $grupo['apellido_paterno'] . ")" : "";
                                                    echo htmlspecialchars($grupo['nombre_materia'] . ' - Grupo: ' . $grupo['nombre_grupo'] . $texto_admin); 
                                                ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($horario_seleccionado)): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-list-ol me-2"></i>Listado de Alumnos</h5>
                            <span class="badge bg-primary px-3 py-2">CURSO EN PROGRESO</span>
                        </div>
                        <div class="card-body">
                            
                            <?php if (count($alumnos_grupo) > 0): ?>
                                <form action="calificaciones.php" method="POST">
                                    <input type="hidden" name="id_horario_seleccionado" value="<?php echo htmlspecialchars($horario_seleccionado); ?>">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead style="color: var(--rojo-vino);">
                                                <tr>
                                                    <th>Matrícula</th>
                                                    <th>Nombre del Alumno</th>
                                                    <th class="text-center">U1</th><th class="text-center">U2</th><th class="text-center">U3</th>
                                                    <th class="text-center">U4</th><th class="text-center">U5</th><th class="text-center">U6</th>
                                                    <th class="text-center">Promedio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alumnos_grupo as $alumno): ?>
                                                    <tr>
                                                        <input type="hidden" name="id_cargas[]" value="<?php echo $alumno['id_carga']; ?>">
                                                        <td class="fw-bold text-muted"><?php echo htmlspecialchars($alumno['matricula']); ?></td>
                                                        <td class="fw-bold"><?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['nombre']); ?></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u1[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u1']; ?>"></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u2[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u2']; ?>"></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u3[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u3']; ?>"></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u4[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u4']; ?>"></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u5[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u5']; ?>"></td>
                                                        <td class="text-center"><input type="number" step="0.1" name="u6[<?php echo $alumno['id_carga']; ?>]" class="input-unidad" value="<?php echo $alumno['u6']; ?>"></td>
                                                        <td class="text-center final-score <?php echo ($alumno['calificacion'] !== null && $alumno['calificacion'] < 70) ? 'text-danger' : 'text-success'; ?>">
                                                            <?php echo $alumno['calificacion'] !== null ? $alumno['calificacion'] : '-'; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4 border-top pt-3">
                                        <button type="submit" name="guardar_calificaciones" class="btn btn-outline-primary px-4 fw-bold"><i class="bi bi-floppy-fill me-2"></i> Guardar Avance</button>
                                        <button type="submit" name="finalizar_curso" class="btn text-white px-4 fw-bold shadow-sm" style="background-color: var(--rojo-vino);" onclick="return confirm('¿Seguro que deseas finalizar el curso? Ya no podrás editar las calificaciones.');"><i class="bi bi-lock-fill me-2"></i> Finalizar Curso e Imprimir</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted"><h5>No hay alumnos inscritos.</h5></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($rol_actual == 'Alumno'): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <h1 class="mb-3 mb-md-0" style="color: var(--rojo-vino); font-weight: bold;">Mis Calificaciones</h1>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background-color: #f8f9fa; color: var(--rojo-vino);">
                                    <tr>
                                        <th class="ps-4 py-3">Materia / Profesor</th>
                                        <th class="text-center py-3" title="Unidad 1">U1</th>
                                        <th class="text-center py-3" title="Unidad 2">U2</th>
                                        <th class="text-center py-3" title="Unidad 3">U3</th>
                                        <th class="text-center py-3" title="Unidad 4">U4</th>
                                        <th class="text-center py-3" title="Unidad 5">U5</th>
                                        <th class="text-center py-3" title="Unidad 6">U6</th>
                                        <th class="text-center py-3 fw-bold">Promedio</th>
                                        <th class="pe-4 py-3 text-center">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($mis_calificaciones) > 0): ?>
                                        <?php foreach ($mis_calificaciones as $mat): ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <strong class="d-block text-dark"><?php echo htmlspecialchars($mat['nombre_materia']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($mat['profe_nombre'] . ' ' . $mat['profe_apellido']); ?></small>
                                                </td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u1'] ?? '-'; ?></td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u2'] ?? '-'; ?></td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u3'] ?? '-'; ?></td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u4'] ?? '-'; ?></td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u5'] ?? '-'; ?></td>
                                                <td class="text-center fw-semibold text-secondary"><?php echo $mat['u6'] ?? '-'; ?></td>
                                                
                                                <td class="text-center py-3">
                                                    <?php if ($mat['calificacion'] !== null): ?>
                                                        <span class="fs-5 fw-bold <?php echo ($mat['calificacion'] >= 70) ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo htmlspecialchars($mat['calificacion']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted fst-italic">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="pe-4 text-center py-3">
                                                    <span class="badge bg-primary px-3 py-2"><i class="bi bi-arrow-repeat me-1"></i>En Curso</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center py-5 text-muted">Aún no tienes materias asignadas en tu semestre actual.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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