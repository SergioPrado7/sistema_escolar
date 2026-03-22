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

$query = "SELECT u.id_usuario, u.matricula, u.correo, u.rol, u.estatus, p.nombre, p.apellido_paterno 
          FROM usuarios u 
          LEFT JOIN personas p ON u.id_usuario = p.id_usuario";
$stmt = $db->prepare($query);
$stmt->execute();
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
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
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
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;">Gestión de Usuarios</h1>
                <button class="btn text-white" style="background-color: var(--rojo-vino);" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                    <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
                </button>
            </div>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <strong>¡Acción denegada!</strong> Ya existe un usuario registrado con esa misma matrícula.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead style="color: var(--rojo-vino);">
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($user['matricula']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']); ?></td>
                                    <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['rol']); ?></span></td>
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
                <select name="rol" class="form-select" required>
                    <option value="Alumno">Alumno</option>
                    <option value="Profesor">Profesor</option>
                    <option value="Administrador">Administrador</option>
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
</body>
</html>