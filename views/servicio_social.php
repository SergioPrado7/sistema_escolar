<?php 
ob_start();
session_start(); 

// -------------------------------------------------------------
// CONEXIÓN A LA BASE DE DATOS
// -------------------------------------------------------------
$host = "localhost"; 
$user = "sitiosergio";
$pass = "j_6)55P_zdHiP8jY2W";
$db   = "sitiosergio_sistema_escolar";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}
$conexion->set_charset("utf8mb4");

// Seguridad: Redirigir si no hay sesión o si es profesor
if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 'Profesor') {
    header("Location: dashboard.php");
    exit();
}

// 1. LÓGICA ALUMNO: Registrar su Servicio Social
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] == 'Alumno' && isset($_POST['registrar_servicio'])) {
    $id_alumno = $_SESSION['id_usuario'];
    $dependencia = $_POST['dependencia'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_termino = $_POST['fecha_termino'];

    $stmt = $conexion->prepare("INSERT INTO servicio_social (id_alumno, dependencia, fecha_inicio, fecha_termino, horas_acumuladas, estatus) VALUES (?, ?, ?, ?, 0, 'Pendiente')");
    $stmt->bind_param("isss", $id_alumno, $dependencia, $fecha_inicio, $fecha_termino);
    $stmt->execute();
    $stmt->close();
    
    header("Location: servicio_social.php?mensaje=registrado");
    exit();
}

// 2. LÓGICA ADMIN: Actualizar horas y estatus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador' && isset($_POST['actualizar_servicio'])) {
    $id_servicio = $_POST['id_servicio'];
    $horas = $_POST['horas'];
    $estatus = $_POST['estatus'];

    $stmt = $conexion->prepare("UPDATE servicio_social SET horas_acumuladas=?, estatus=? WHERE id_servicio=?");
    $stmt->bind_param("isi", $horas, $estatus, $id_servicio);
    $stmt->execute();
    $stmt->close();
    
    header("Location: servicio_social.php?mensaje=actualizado");
    exit();
}

// Variables para el Admin
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_estatus = $_GET['estatus_filter'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio Social - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/servicioIcono.ico">
</head>
<body>

<div class="container-fluid p-0 d-flex flex-column flex-md-row">
    
    <nav class="sidebar d-none d-md-flex">
        <div class="logo_foto">
            <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
        </div>
        <div class="menu_links">
            <a href="dashboard.php" class="item">Panel Principal</a>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
            <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
            <?php endif; ?>
            <a href="horarios.php" class="item">Horarios</a>
            <a href="calificaciones.php" class="item">Calificaciones</a>
            <a href="finanzas.php" class="item">Finanzas y Pagos</a>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
            <a href="gestion_academica.php" class="item">Gestión Academica</a>
            <?php endif; ?>
            <a href="servicio_social.php" class="item active">Servicio Social</a>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Alumno'): ?>
            <a href="kardex.php" class="item active">Kardex</a>
            <?php endif; ?>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Servicio Social</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                    <?php endif; ?>
                    <a href="horarios.php" class="item">Horarios</a>
                    <a href="calificaciones.php" class="item">Calificaciones</a>
                    <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="gestion_academica.php" class="item">Gestión Academica</a>
                    <?php endif; ?>
                    <a href="servicio_social.php" class="item active">Servicio Social</a>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Alumno'): ?>
                    <a href="kardex.php" class="item active">Kardex</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;">Servicio Social</h1>
            </div>

            <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <strong>¡Acción Exitosa!</strong> 
                    <?php 
                        if($_GET['mensaje'] == 'registrado') echo "Tu trámite de servicio social ha sido registrado.";
                        if($_GET['mensaje'] == 'actualizado') echo "El registro del alumno se ha actualizado correctamente.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form action="servicio_social.php" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-search"></i> Matrícula o Nombre del Alumno</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-funnel"></i> Estatus</label>
                                <select name="estatus_filter" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Pendiente" <?php echo ($filtro_estatus == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="En Proceso" <?php echo ($filtro_estatus == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="Liberado" <?php echo ($filtro_estatus == 'Liberado') ? 'selected' : ''; ?>>Liberado</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2">
                                <button type="submit" class="btn text-white w-100" style="background-color: var(--rojo-vino);">Filtrar</button>
                                <?php if(!empty($filtro_busqueda) || !empty($filtro_estatus)): ?>
                                    <a href="servicio_social.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-eraser-fill"></i></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-building-check me-2"></i>Gestión de Servicio Social</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead style="color: var(--rojo-vino);">
                                    <tr>
                                        <th>Matrícula</th>
                                        <th>Alumno</th>
                                        <th>Dependencia</th>
                                        <th>Horas</th>
                                        <th>Estatus</th>
                                        <th>Acciones</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_todos = "SELECT s.*, p.nombre, p.apellido_paterno, u.matricula 
                                                  FROM servicio_social s
                                                  JOIN personas p ON s.id_alumno = p.id_usuario
                                                  JOIN usuarios u ON s.id_alumno = u.id_usuario
                                                  WHERE 1=1 ";
                                    
                                    if (!empty($filtro_busqueda)) {
                                        $busqueda = $conexion->real_escape_string($filtro_busqueda);
                                        $sql_todos .= " AND (u.matricula LIKE '%$busqueda%' OR p.nombre LIKE '%$busqueda%' OR p.apellido_paterno LIKE '%$busqueda%') ";
                                    }
                                    if (!empty($filtro_estatus)) {
                                        $est = $conexion->real_escape_string($filtro_estatus);
                                        $sql_todos .= " AND s.estatus = '$est' ";
                                    }
                                    
                                    $res_todos = $conexion->query($sql_todos);

                                    if ($res_todos && $res_todos->num_rows > 0) {
                                        while($s = $res_todos->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold text-muted'>".$s['matricula']."</td>";
                                            echo "<td class='fw-bold'>".$s['nombre']." ".$s['apellido_paterno']."</td>";
                                            echo "<td>".$s['dependencia']."</td>";
                                            echo "<td><strong>".$s['horas_acumuladas']." / 480</strong></td>";
                                            
                                            $color_badge = 'bg-secondary';
                                            if($s['estatus'] == 'Pendiente') $color_badge = 'bg-warning text-dark';
                                            if($s['estatus'] == 'En Proceso') $color_badge = 'bg-primary';
                                            if($s['estatus'] == 'Liberado') $color_badge = 'bg-success';
                                            
                                            echo "<td><span class='badge ".$color_badge." px-2 py-1'>".$s['estatus']."</span></td>";
                                            
                                            echo "<td>
                                                    <button class='btn btn-sm btn-outline-primary shadow-sm' 
                                                        onclick='abrirModalServicio(".$s['id_servicio'].", ".$s['horas_acumuladas'].", \"".$s['estatus']."\")' 
                                                        title='Editar Horas y Estatus'>
                                                        <i class='bi bi-pencil-square'></i> Validar
                                                    </button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'><i class='bi bi-search fs-2'></i><br>No se encontraron registros.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Alumno'): ?>
                
                <?php
                // Verificamos si el alumno ya tiene un registro
                $id_actual = $_SESSION['id_usuario'];
                $sql_mi_servicio = "SELECT * FROM servicio_social WHERE id_alumno = ?";
                $stmt_mi = $conexion->prepare($sql_mi_servicio);
                $stmt_mi->bind_param("i", $id_actual);
                $stmt_mi->execute();
                $res_mi = $stmt_mi->get_result();
                $mi_servicio = $res_mi->fetch_assoc();
                ?>

                <?php if (!$mi_servicio): ?>
                    <div class="card shadow-sm border-0 mb-4 mx-auto" style="max-width: 700px;">
                        <div class="card-header text-white pt-4 pb-3" style="background-color: var(--rojo-vino);">
                            <h5 class="mb-0 text-center"><i class="bi bi-file-earmark-text me-2"></i>Iniciar Trámite de Servicio Social</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i> Llena este formulario únicamente si ya fuiste aceptado en una dependencia para realizar tu servicio.
                            </div>
                            <form method="POST" action="servicio_social.php">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Dependencia o Empresa</label>
                                    <input type="text" name="dependencia" class="form-control" required placeholder="Ej: Presidencia Municipal, Escuela primaria, etc.">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Fecha de Inicio</label>
                                        <input type="date" name="fecha_inicio" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Fecha de Término Estimada</label>
                                        <input type="date" name="fecha_termino" class="form-control" required>
                                    </div>
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" name="registrar_servicio" class="btn text-white py-2" style="background-color: var(--rojo-vino);">
                                        Registrar Servicio Social
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <?php 
                        $porcentaje = ($mi_servicio['horas_acumuladas'] / 480) * 100;
                        if($porcentaje > 100) $porcentaje = 100;
                        
                        $color_badge = 'bg-secondary';
                        if($mi_servicio['estatus'] == 'Pendiente') $color_badge = 'bg-warning text-dark';
                        if($mi_servicio['estatus'] == 'En Proceso') $color_badge = 'bg-primary';
                        if($mi_servicio['estatus'] == 'Liberado') $color_badge = 'bg-success';
                    ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-person-workspace me-2"></i>Mi Expediente</h5>
                            <span class="badge <?php echo $color_badge; ?> fs-6"><?php echo $mi_servicio['estatus']; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <p class="text-muted mb-1">Dependencia Asignada</p>
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($mi_servicio['dependencia']); ?></h6>
                                </div>
                                <div class="col-md-4">
                                    <p class="text-muted mb-1">Fecha de Inicio</p>
                                    <h6 class="fw-bold"><?php echo $mi_servicio['fecha_inicio']; ?></h6>
                                </div>
                                <div class="col-md-4">
                                    <p class="text-muted mb-1">Fecha de Término</p>
                                    <h6 class="fw-bold"><?php echo $mi_servicio['fecha_termino']; ?></h6>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3">Progreso de Horas (<?php echo $mi_servicio['horas_acumuladas']; ?> / 480)</h6>
                            <div class="progress" style="height: 25px; border-radius: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo ($porcentaje == 100) ? 'bg-success' : 'bg-primary'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $porcentaje; ?>%;" 
                                     aria-valuenow="<?php echo $porcentaje; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo round($porcentaje); ?>%
                                </div>
                            </div>
                            
                            <?php if($mi_servicio['estatus'] == 'Liberado'): ?>
                                <div class="alert alert-success mt-4 mb-0 text-center shadow-sm">
                                    <i class="bi fs-3 d-block mb-2 text-warning"></i>
                                    <strong>¡Felicidades!</strong> Has completado satisfactoriamente tus 480 horas y tu Servicio Social ha sido liberado.
                                    <br><br>
                                    <a href="constancia_servicio.php?id=<?php echo $mi_servicio['id_servicio']; ?>" target="_blank" class="btn btn-success mt-2 fw-bold shadow-sm">
                                        <i class="bi me-2">Imprimir Constancia Terminación</i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary mt-4 mb-0 text-center">
                                    <i class="bi bi-info-circle me-2"></i> Entrega tus reportes bimestrales en control escolar para que el administrador valide tus horas.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>
</div>

<?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
<div class="modal fade" id="modalValidarServicio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
        <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Validar Servicio Social</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="servicio_social.php">
          <div class="modal-body">
            <input type="hidden" name="id_servicio" id="edit_id_servicio">
            
            <div class="alert alert-warning mb-3">
                <small>Recuerda que el total para liberar el servicio es de <strong>480 horas</strong>.</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Horas Acumuladas</label>
                <input type="number" name="horas" id="edit_horas" class="form-control form-control-lg" required min="0" max="480">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Estatus del Trámite</label>
                <select name="estatus" id="edit_estatus" class="form-select form-select-lg" required>
                    <option value="Pendiente">Pendiente (Revisión)</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Liberado">Liberado (Terminado)</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="actualizar_servicio" class="btn text-white" style="background-color: var(--rojo-vino);">Guardar Progreso</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalServicio(id, horas, estatus) {
    document.getElementById('edit_id_servicio').value = id;
    document.getElementById('edit_horas').value = horas;
    document.getElementById('edit_estatus').value = estatus;
    
    var modal = new bootstrap.Modal(document.getElementById('modalValidarServicio'));
    modal.show();
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>