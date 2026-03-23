<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: dashboard.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();

// 1. Obtener las carreras para los selects (Filtro y Modal)
$queryCarreras = "SELECT id_carrera, nombre_carrera FROM carreras";
$stmtCarreras = $db->prepare($queryCarreras);
$stmtCarreras->execute();
$carreras = $stmtCarreras->fetchAll(PDO::FETCH_ASSOC);

// 2. RECIBIR VARIABLES DEL FILTRO
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_rol = $_GET['rol_filter'] ?? '';
$filtro_carrera = $_GET['carrera_filter'] ?? '';

// 3. CONSTRUIR LA CONSULTA DINÁMICAMENTE
$query = "SELECT u.id_usuario, u.matricula, u.correo, u.rol, u.estatus, p.nombre, p.apellido_paterno, c.nombre_carrera 
          FROM usuarios u 
          LEFT JOIN personas p ON u.id_usuario = p.id_usuario
          LEFT JOIN alumnos_detalles ad ON u.id_usuario = ad.id_alumno
          LEFT JOIN carreras c ON ad.id_carrera = c.id_carrera";

$condiciones = [];
$parametros = [];

// Si escribieron algo en la barra de búsqueda (Matrícula o Nombre)
if (!empty($filtro_busqueda)) {
    $condiciones[] = "(u.matricula LIKE :busqueda OR p.nombre LIKE :busqueda OR p.apellido_paterno LIKE :busqueda)";
    $parametros[':busqueda'] = "%" . $filtro_busqueda . "%";
}

// Si seleccionaron un rol
if (!empty($filtro_rol)) {
    $condiciones[] = "u.rol = :rol";
    $parametros[':rol'] = $filtro_rol;
}

// Si seleccionaron una carrera
if (!empty($filtro_carrera)) {
    $condiciones[] = "ad.id_carrera = :carrera";
    $parametros[':carrera'] = $filtro_carrera;
}

// Unir condiciones a la consulta principal si existen
if (count($condiciones) > 0) {
    $query .= " WHERE " . implode(" AND ", $condiciones);
}

// Ejecutar la consulta final
$stmt = $db->prepare($query);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
</head>
<body>

<div class="container-fluid p-0 d-flex flex-column flex-md-row">
    
        <nav class="sidebar d-none d-md-flex">
            <div class="logo_foto">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
            </div>
            <div class="menu_links">
                
                <a href="dashboard.php" class="item">Panel Principal</a>
                
                <?php if ($_SESSION['rol'] == 'Administrador'): ?>
                <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                <?php endif; ?>

                <a href = "horarios.php" class="item">Horarios</a>
                
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

            </div>
        </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Gestión de Usuarios</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <a href="gestion_usuarios.php" class="item active">Gestión Usuarios</a>
                    <a href = "horarios.php" class="item">Horarios</a>
                    <a href="calificaciones.php" class="item active">Calificaciones</a>
                    <a href="finanzas.php" class="item active">Finanzas y Pagos</a>
                    <a href="gestion_academica.php" class="item active">Gestión Académica</a>
                    <a href="servicio_social.php" class="item active">Servicio Social</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;">Gestión de Usuarios</h1>
                <button class="btn text-white shadow-sm" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                    <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
                </button>
            </div>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <strong>¡Acción denegada!</strong> Ya existe un usuario registrado con esa misma matrícula.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="gestion_usuarios.php" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-search"></i> Matrícula o Nombre</label>
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-person-badge"></i> Rol</label>
                            <select name="rol_filter" class="form-select">
                                <option value="">Todos los roles</option>
                                <option value="Alumno" <?php echo ($filtro_rol == 'Alumno') ? 'selected' : ''; ?>>Alumno</option>
                                <option value="Profesor" <?php echo ($filtro_rol == 'Profesor') ? 'selected' : ''; ?>>Profesor</option>
                                <option value="Administrador" <?php echo ($filtro_rol == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-book"></i> Carrera</label>
                            <select name="carrera_filter" class="form-select">
                                <option value="">Todas las carreras</option>
                                <?php foreach ($carreras as $carrera): ?>
                                    <option value="<?php echo $carrera['id_carrera']; ?>" <?php echo ($filtro_carrera == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($carrera['nombre_carrera']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn text-white w-100" style="background-color: var(--rojo-vino);">Filtrar</button>
                            <?php if(!empty($filtro_busqueda) || !empty($filtro_rol) || !empty($filtro_carrera)): ?>
                                <a href="gestion_usuarios.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-eraser-fill"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="color: var(--rojo-vino);">
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Carrera</th> 
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($usuarios) > 0): ?>
                                    <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($user['matricula']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']); ?></td>
                                        <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['rol']); ?></span></td>
                                        
                                        <td>
                                            <?php if($user['rol'] == 'Alumno'): ?>
                                                <span class="text-muted"><?php echo htmlspecialchars($user['nombre_carrera'] ?? 'Sin asignar'); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fw-bold">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if($user['estatus'] == 'Activo'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="editar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="../controllers/eliminar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas dar de baja este usuario?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-search fs-2"></i><br>
                                            No se encontraron usuarios que coincidan con la búsqueda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
        <h5 class="modal-title" id="modalLabel">Registrar Nuevo Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../controllers/guardar_usuario.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Matrícula</label>
                <input type="text" name="matricula" class="form-control" required>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Apellido Paterno</label>
                    <input type="text" name="apellido" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Rol del Sistema</label>
                <select name="rol" id="selectRol" class="form-select" required>
                    <option value="">Selecciona un rol...</option>
                    <option value="Alumno">Alumno</option>
                    <option value="Profesor">Profesor</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>

            <div class="mb-3" id="divCarrera" style="display: none;">
                <label class="form-label">Carrera (Solo Alumnos)</label>
                <select name="id_carrera" id="selectCarrera" class="form-select">
                    <option value="">Selecciona una carrera...</option>
                    <?php foreach ($carreras as $carrera): ?>
                        <option value="<?php echo $carrera['id_carrera']; ?>">
                            <?php echo htmlspecialchars($carrera['nombre_carrera']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña Temporal</label>
                <input type="password" name="password" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn text-white" style="background-color: var(--rojo-vino);">Guardar Usuario</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
    document.getElementById('selectRol').addEventListener('change', function() {
        var divCarrera = document.getElementById('divCarrera');
        var selectCarrera = document.getElementById('selectCarrera');
        
        if(this.value === 'Alumno') {
            divCarrera.style.display = 'block'; 
            selectCarrera.setAttribute('required', 'required'); 
        } else {
            divCarrera.style.display = 'none'; 
            selectCarrera.removeAttribute('required'); 
            selectCarrera.value = ''; 
        }
    });
</script>

</body>
</html>