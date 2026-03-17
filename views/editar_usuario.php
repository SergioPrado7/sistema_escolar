<?php
session_start();
require_once '../config/database.php';

// Seguridad
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: gestion_usuarios.php");
    exit();
}

$id_usuario = $_GET['id'];
$conexion = new Conexion();
$db = $conexion->getConnection();

// Traer los datos actuales del usuario
$query = "SELECT u.id_usuario, u.matricula, u.rol, u.estatus, p.nombre, p.apellido_paterno 
          FROM usuarios u 
          LEFT JOIN personas p ON u.id_usuario = p.id_usuario 
          WHERE u.id_usuario = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Tec San Pedro</title>
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/estilo.css">
</head>
<body style="background-color: #f8f9fa;">
<div class="container mt-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 600px;">
        <div class="card-header text-white" style="background-color: #7B1E27;">
            <h4 class="mb-0">Editar Usuario: <?php echo htmlspecialchars($usuario['matricula']); ?></h4>
        </div>
        <div class="card-body">
            <form action="../controllers/actualizar_usuario.php" method="POST">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellido Paterno</label>
                        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['apellido_paterno']); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol del Sistema</label>
                    <select name="rol" class="form-select" required>
                        <option value="Alumno" <?php if($usuario['rol'] == 'Alumno') echo 'selected'; ?>>Alumno</option>
                        <option value="Profesor" <?php if($usuario['rol'] == 'Profesor') echo 'selected'; ?>>Profesor</option>
                        <option value="Administrador" <?php if($usuario['rol'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select" required>
                        <option value="Activo" <?php if($usuario['estatus'] == 'Activo') echo 'selected'; ?>>Activo</option>
                        <option value="Inactivo" <?php if($usuario['estatus'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn text-white" style="background-color: #7B1E27;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>