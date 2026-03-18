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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    
    <style>
        .item.active-page {
            background-color: #5a151c !important; /* Rojo más oscuro */
            transform: scale(1.02);
            box-shadow: inset 0 0 10px rgba(0,0,0,0.3);
        }
        
        .main_contenido {
            background-color: #f8f9fa;
        }
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
                <a href="gestion_usuarios.php" class="item active-page">Gestión Usuarios</a>
                <a href="#" class="item">Mis Calificaciones</a>
                <a href="#" class="item">Finanzas y Pagos</a>
                <a href="#" class="item">Carga Academica</a>
                <a href="#" class="item">Servicio Social</a>
            </div>
        </nav>

        <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="text-white fw-bold">Gestor de Usuarios</span>
                <div class="collapse navbar-collapse" id="menuMovil">
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="dashboard.php" class="item">Panel Principal</a>
                        <a href="gestion_usuarios.php" class="item active-page">Gestión Usuarios</a>
                        <a href="#" class="item">Carga Academica</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main_contenido">
            <header class="top_header">
                <h1 class="titulo text-center flex-grow-1">Gestión de Usuarios</h1>
                <div class="perfil">
                    <span class="perfil_texto">Mi Perfil</span>
                    <div class="perfil_foto"></div>
                </div>
            </header>

            <section class="dashboard container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                    <h2 class="seccion_titulo m-0">Lista de Usuarios</h2>
                    <button class="btn text-white" style="background-color: var(--rojo-vino); border-radius: 10px; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                        <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
                    </button>
                </div>

                <?php if(isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <strong>¡Acción denegada!</strong> Ya existe un usuario registrado con esa misma matrícula.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead style="background-color: var(--gris-suave); color: var(--rojo-vino);">
                                    <tr>
                                        <th class="ps-4">Matrícula</th>
                                        <th>Nombre Completo</th>
                                        <th>Rol</th>
                                        <th>Estatus</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td class="fw-bold ps-4"><?php echo htmlspecialchars($user['matricula']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']); ?></td>
                                        <td><span class="badge rounded-pill bg-secondary px-3"><?php echo htmlspecialchars($user['rol']); ?></span></td>
                                        <td>
                                            <?php if($user['estatus'] == 'Activo'): ?>
                                                <span class="badge rounded-pill bg-success px-3">Activo</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-danger px-3">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="editar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-outline-primary border-0"><i class="bi bi-pencil-square fs-5"></i></a>
                                            <a href="../controllers/eliminar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('¿Seguro que deseas dar de baja este usuario?');"><i class="bi bi-trash3-fill fs-5"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden; border: none;">
          <div class="modal-header" style="background-color: var(--rojo-vino); color: white;">
            <h5 class="modal-title fw-bold">Registrar Nuevo Usuario</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="../controllers/guardar_usuario.php" method="POST">
              <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Matrícula</label>
                    <input type="text" name="matricula" class="form-control" style="border-radius: 8px;" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Nombre</label>
                        <input type="text" name="nombre" class="form-control" style="border-radius: 8px;" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Apellido Paterno</label>
                        <input type="text" name="apellido" class="form-control" style="border-radius: 8px;" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Rol del Sistema</label>
                    <select name="rol" class="form-select" style="border-radius: 8px;" required>
                        <option value="Alumno">Alumno</option>
                        <option value="Profesor">Profesor</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña Temporal</label>
                    <input type="password" name="password" class="form-control" style="border-radius: 8px;" required>
                </div>
              </div>
              <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                <button type="submit" class="btn text-white px-4" style="background-color: var(--rojo-vino); border-radius: 8px;">Guardar Usuario</button>
              </div>
          </form>
        </div>
      </div>
    </div>
</body>
</html>