<?php
session_start();
require_once '../config/database.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: dashboard.php");
    exit();
}


$conexion = new Conexion();
$db = $conexion->getConnection();


$query = "SELECT u.id_usuario, u.matricula, u.rol, u.estatus, p.nombre, p.apellido_paterno 
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
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        /* Ajustes únicamente para responsividad. Tu diseño queda intacto. */
        @media (min-width: 768px) {
            .sidebar-responsivo { min-height: 100vh; position: sticky; top: 0; }
        }
        @media (max-width: 767px) {
            .sidebar-responsivo { border-right: none !important; border-bottom: 1px solid #ddd; padding-bottom: 20px !important; }
            .header-movil { flex-direction: column; gap: 15px; text-align: center; }
            .header-movil h1 { font-size: 1.8rem; }
        }
        /* Evita que se vea deforme en monitores ultra anchos */
        @media (min-width: 1400px) {
            .contenedor-limite { max-width: 1500px; margin: 0 auto; }
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="container-fluid contenedor-limite">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 p-3 bg-white sidebar-responsivo" style="border-right: 1px solid #ddd;">
            <div class="text-center mb-4">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Tec San Pedro" class="img-fluid" style="max-width: 150px;">
            </div>
            <div class="d-grid gap-2">
                <a href="dashboard.php" class="btn text-white" style="background-color: #800020;">Panel Principal</a>
                <a href="gestion_usuarios.php" class="btn text-white fw-bold" style="background-color: #5a151c;">Gestión Usuarios</a>
                <a href="#" class="btn text-white" style="background-color: #800020;">Carga Académica</a>
            </div>
        </div>

        <div class="col-12 col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 header-movil">
                <h1 style="color: #800020; font-weight: bold;">Gestión de Usuarios</h1>
                <button class="btn text-white" style="background-color: #800020;" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
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
                            <thead style="color: #800020;">
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Nombre Completo</th>
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
    </div>
</div>

<script src="../node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
<script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #800020; color: white;">
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
                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control" required>
                </div>
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
            <button type="submit" class="btn text-white" style="background-color: #800020;">Guardar Usuario</button>
          </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>