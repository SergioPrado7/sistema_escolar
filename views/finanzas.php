<?php 
// Evitamos que cualquier espacio en blanco rompa la sesión
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

// Verificamos la conexión de forma limpia
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$conexion->set_charset("utf8mb4");

// Seguridad: Redirigir si no hay sesión o si es profesor
if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 'Profesor') {
    header("Location: dashboard.php");
    exit();
}

// 1. LÓGICA: Guardar NUEVO cargo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador' && isset($_POST['asignar_cargo'])) {
    $id_alumno = $_POST['id_alumno'];
    $concepto = $_POST['concepto'];
    $monto = $_POST['monto'];
    $fecha_limite = $_POST['fecha_limite'];

    $stmt = $conexion->prepare("INSERT INTO cargos_financieros (id_alumno, concepto, monto, fecha_limite) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $id_alumno, $concepto, $monto, $fecha_limite);
    $stmt->execute();
    $stmt->close();
    
    header("Location: finanzas.php?mensaje=asignado");
    exit();
}

// 2. LÓGICA: Editar cargo EXISTENTE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador' && isset($_POST['editar_cargo'])) {
    $id_cargo = $_POST['id_cargo'];
    $concepto = $_POST['concepto_edit'];
    $monto = $_POST['monto_edit'];
    $fecha_limite = $_POST['fecha_limite_edit'];
    $estatus = $_POST['estatus_edit'];

    $stmt = $conexion->prepare("UPDATE cargos_financieros SET concepto=?, monto=?, fecha_limite=?, estatus=? WHERE id_cargo=?");
    $stmt->bind_param("sdssi", $concepto, $monto, $fecha_limite, $estatus, $id_cargo);
    $stmt->execute();
    $stmt->close();
    
    header("Location: finanzas.php?mensaje=editado");
    exit();
}

// 3. LÓGICA: Eliminar cargo
if (isset($_GET['eliminar']) && isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador') {
    $id_eliminar = $_GET['eliminar'];
    
    $stmt = $conexion->prepare("DELETE FROM cargos_financieros WHERE id_cargo = ?");
    $stmt->bind_param("i", $id_eliminar);
    $stmt->execute();
    $stmt->close();
    
    header("Location: finanzas.php?mensaje=eliminado");
    exit();
}

// 4. FILTROS PARA EL ADMINISTRADOR
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_carrera = $_GET['carrera_filter'] ?? '';

// Obtener carreras para el select del filtro
$sql_carreras = "SELECT id_carrera, nombre_carrera FROM carreras";
$res_carreras = $conexion->query($sql_carreras);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzas y Pagos - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/finanzasIcono.ico">
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
            <a href="#" class="item">Calificaciones</a>
            <a href="finanzas.php" class="item active">Finanzas y Pagos</a>
            <a href="#" class="item">Carga Academica</a>
            <a href="#" class="item">Servicio Social</a>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Finanzas y Pagos</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                    <?php endif; ?>
                    <a href="#" class="item">Calificaciones</a>
                    <a href="finanzas.php" class="item active">Finanzas y Pagos</a>
                    <a href="#" class="item">Carga Academica</a>
                    <a href="#" class="item">Servicio Social</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;">Finanzas y Pagos</h1>
            </div>

            <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <strong>¡Acción Exitosa!</strong> 
                    <?php 
                        if($_GET['mensaje'] == 'asignado') echo "Cargo asignado correctamente.";
                        if($_GET['mensaje'] == 'editado') echo "Cargo actualizado correctamente.";
                        if($_GET['mensaje'] == 'eliminado') echo "Cargo eliminado del sistema.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-cash-coin me-2"></i>Asignar Nuevo Cargo a Alumno</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="finanzas.php">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Alumno:</label>
                                    <select name="id_alumno" class="form-select" required>
                                        <option value="">Selecciona un alumno...</option>
                                        <?php
                                        try {
                                            $sql_alumnos = "SELECT u.id_usuario, p.nombre, p.apellido_paterno 
                                                            FROM usuarios u 
                                                            JOIN personas p ON u.id_usuario = p.id_usuario 
                                                            WHERE u.rol = 'Alumno' AND u.estatus = 'Activo'";
                                            $res_alumnos = $conexion->query($sql_alumnos);
                                            while($row = $res_alumnos->fetch_assoc()) {
                                                echo "<option value='".$row['id_usuario']."'>".$row['nombre']." ".$row['apellido_paterno']."</option>";
                                            }
                                        } catch (Exception $e) {
                                            echo "<option value=''>Error al cargar alumnos</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Concepto:</label>
                                    <select name="concepto" class="form-select" required>
                                        <option value="Inscripción">Inscripción</option>
                                        <option value="Reinscripción">Reinscripción</option>
                                        <option value="Semestre">Semestre</option>
                                        <option value="Inglés">Inglés</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Monto ($):</label>
                                    <input type="number" step="0.01" name="monto" class="form-control" required placeholder="Ej: 2500.00">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Fecha Límite:</label>
                                    <input type="date" name="fecha_limite" class="form-control" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" name="asignar_cargo" class="btn text-white px-4" style="background-color: var(--rojo-vino);">
                                    <i class="bi bi-plus-circle me-1"></i> Guardar Cargo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form action="finanzas.php" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-search"></i> Matrícula o Nombre del Alumno</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-book"></i> Carrera</label>
                                <select name="carrera_filter" class="form-select">
                                    <option value="">Todas las carreras</option>
                                    <?php 
                                    if($res_carreras) {
                                        while ($carrera = $res_carreras->fetch_assoc()) {
                                            $selected = ($filtro_carrera == $carrera['id_carrera']) ? 'selected' : '';
                                            echo "<option value='{$carrera['id_carrera']}' {$selected}>" . htmlspecialchars($carrera['nombre_carrera']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button type="submit" class="btn text-white w-100" style="background-color: var(--rojo-vino);">Filtrar</button>
                                <?php if(!empty($filtro_busqueda) || !empty($filtro_carrera)): ?>
                                    <a href="finanzas.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-eraser-fill"></i></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-list-check me-2"></i>Gestión de Cargos Asignados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead style="color: var(--rojo-vino);">
                                    <tr>
                                        <th>Matrícula</th>
                                        <th>Alumno</th>
                                        <th>Carrera</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Fecha Límite</th>
                                        <th>Estatus</th>
                                        <th>Acciones</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Consulta dinámica con filtros
                                    $sql_todos = "SELECT c.*, p.nombre, p.apellido_paterno, u.matricula, car.nombre_carrera 
                                                  FROM cargos_financieros c
                                                  JOIN personas p ON c.id_alumno = p.id_usuario
                                                  JOIN usuarios u ON c.id_alumno = u.id_usuario
                                                  LEFT JOIN alumnos_detalles ad ON c.id_alumno = ad.id_alumno
                                                  LEFT JOIN carreras car ON ad.id_carrera = car.id_carrera
                                                  WHERE 1=1 ";
                                    
                                    if (!empty($filtro_busqueda)) {
                                        $busqueda = $conexion->real_escape_string($filtro_busqueda);
                                        $sql_todos .= " AND (u.matricula LIKE '%$busqueda%' OR p.nombre LIKE '%$busqueda%' OR p.apellido_paterno LIKE '%$busqueda%') ";
                                    }

                                    if (!empty($filtro_carrera)) {
                                        $carrera_id = (int)$filtro_carrera;
                                        $sql_todos .= " AND ad.id_carrera = $carrera_id ";
                                    }

                                    $sql_todos .= " ORDER BY c.fecha_limite ASC";
                                    
                                    $res_todos = $conexion->query($sql_todos);

                                    if ($res_todos && $res_todos->num_rows > 0) {
                                        while($c = $res_todos->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold text-muted'>".$c['matricula']."</td>";
                                            echo "<td class='fw-bold'>".$c['nombre']." ".$c['apellido_paterno']."</td>";
                                            echo "<td><span class='text-muted' style='font-size: 0.9em;'>".($c['nombre_carrera'] ?? 'Sin asignar')."</span></td>";
                                            echo "<td>".$c['concepto']."</td>";
                                            echo "<td>$".number_format($c['monto'], 2)."</td>";
                                            echo "<td>".$c['fecha_limite']."</td>";
                                            
                                            $color_badge = ($c['estatus'] == 'Pendiente') ? 'bg-danger' : 'bg-success';
                                            echo "<td><span class='badge ".$color_badge." px-2 py-1'>".$c['estatus']."</span></td>";
                                            
                                            // Botones de Acción (Editar abre modal, Eliminar manda por GET)
                                            echo "<td>
                                                    <button class='btn btn-sm btn-outline-primary shadow-sm me-1' 
                                                        onclick='abrirModalEditar(".$c['id_cargo'].", \"".$c['concepto']."\", ".$c['monto'].", \"".$c['fecha_limite']."\", \"".$c['estatus']."\")' 
                                                        title='Editar'>
                                                        <i class='bi bi-pencil'></i>
                                                    </button>
                                                    <a href='finanzas.php?eliminar=".$c['id_cargo']."' class='btn btn-sm btn-outline-danger shadow-sm' onclick='return confirm(\"¿Seguro que deseas eliminar este cargo?\");' title='Eliminar'>
                                                        <i class='bi bi-trash'></i>
                                                    </a>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center py-5 text-muted'><i class='bi bi-search fs-2'></i><br>No se encontraron cargos con esos filtros.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Alumno'): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-receipt me-2"></i>Mis Cargos Pendientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead style="color: var(--rojo-vino);">
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Fecha Límite</th>
                                        <th>Estatus</th>
                                        <th>Acción</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $id_actual = $_SESSION['id_usuario'] ?? 0; 
                                        
                                        if($id_actual > 0) {
                                            $sql_cargos = "SELECT * FROM cargos_financieros WHERE id_alumno = ? ORDER BY fecha_limite ASC";
                                            $stmt_cargos = $conexion->prepare($sql_cargos);
                                            $stmt_cargos->bind_param("i", $id_actual);
                                            $stmt_cargos->execute();
                                            $res_cargos = $stmt_cargos->get_result();

                                            if ($res_cargos->num_rows > 0) {
                                                while($cargo = $res_cargos->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td class='fw-bold'>".$cargo['concepto']."</td>";
                                                    echo "<td>$".number_format($cargo['monto'], 2)."</td>";
                                                    echo "<td>".$cargo['fecha_limite']."</td>";
                                                    
                                                    if ($cargo['estatus'] == 'Pendiente') {
                                                        echo "<td><span class='badge bg-danger px-3 py-2'>Pendiente</span></td>";
                                                        echo "<td><a href='ticket.php?id=".$cargo['id_cargo']."' target='_blank' class='btn btn-sm btn-outline-secondary shadow-sm'><i class='bi bi-printer'></i> Imprimir Ticket</a></td>";
                                                    } else {
                                                        echo "<td><span class='badge bg-success px-3 py-2'>Pagado</span></td>";
                                                        echo "<td><span class='text-muted'><i class='bi bi-check-circle-fill text-success'></i> Completado</span></td>";
                                                    }
                                                    
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No tienes cargos pendientes. ¡Todo al corriente!</td></tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center py-4 text-danger'>Error: No se encontró tu ID de usuario en la sesión.</td></tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='5' class='text-center py-4 text-danger'>Error al consultar cargos.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
<div class="modal fade" id="modalEditarCargo" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
        <h5 class="modal-title" id="modalLabel">Editar Cargo Financiero</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="finanzas.php">
          <div class="modal-body">
            <input type="hidden" name="id_cargo" id="edit_id_cargo">
            
            <div class="mb-3">
                <label class="form-label">Concepto</label>
                <select name="concepto_edit" id="edit_concepto" class="form-select" required>
                    <option value="Inscripción">Inscripción</option>
                    <option value="Reinscripción">Reinscripción</option>
                    <option value="Semestre">Semestre</option>
                    <option value="Inglés">Inglés</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Monto ($)</label>
                <input type="number" step="0.01" name="monto_edit" id="edit_monto" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha Límite</label>
                <input type="date" name="fecha_limite_edit" id="edit_fecha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Estatus de Pago</label>
                <select name="estatus_edit" id="edit_estatus" class="form-select" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Pagado">Pagado</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="editar_cargo" class="btn text-white" style="background-color: var(--rojo-vino);">Actualizar Cargo</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
// Función para llenar el modal con los datos del cargo a editar
function abrirModalEditar(id, concepto, monto, fecha, estatus) {
    document.getElementById('edit_id_cargo').value = id;
    document.getElementById('edit_concepto').value = concepto;
    document.getElementById('edit_monto').value = monto;
    document.getElementById('edit_fecha').value = fecha;
    document.getElementById('edit_estatus').value = estatus;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditarCargo'));
    modal.show();
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>