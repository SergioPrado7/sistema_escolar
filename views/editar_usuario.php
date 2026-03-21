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

// Traer los datos actuales del usuario (incluyendo su carrera si es alumno)
$query = "SELECT u.id_usuario, u.matricula, u.rol, u.estatus, p.nombre, p.apellido_paterno, ad.id_carrera 
          FROM usuarios u 
          LEFT JOIN personas p ON u.id_usuario = p.id_usuario 
          LEFT JOIN alumnos_detalles ad ON u.id_usuario = ad.id_alumno
          WHERE u.id_usuario = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el usuario no existe, lo regresamos
if (!$usuario) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Traer las carreras disponibles para el select
$queryCarreras = "SELECT id_carrera, nombre_carrera FROM carreras";
$stmtCarreras = $db->prepare($queryCarreras);
$stmtCarreras->execute();
$carreras = $stmtCarreras->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Tec San Pedro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/editarIcono.ico">
</head>
<body style="background-color: #f8f9fa;">
<div class="container mt-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 600px;">
        <div class="card-header text-white" style="background-color: #800020;">
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
                    <select name="rol" id="selectRol" class="form-select" required>
                        <option value="Alumno" <?php if($usuario['rol'] == 'Alumno') echo 'selected'; ?>>Alumno</option>
                        <option value="Profesor" <?php if($usuario['rol'] == 'Profesor') echo 'selected'; ?>>Profesor</option>
                        <option value="Administrador" <?php if($usuario['rol'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
                    </select>
                </div>

                <div class="mb-3" id="divCarrera" style="display: <?php echo ($usuario['rol'] == 'Alumno') ? 'block' : 'none'; ?>;">
                    <label class="form-label">Carrera Asignada (Solo Alumnos)</label>
                    <select name="id_carrera" id="selectCarrera" class="form-select" <?php echo ($usuario['rol'] == 'Alumno') ? 'required' : ''; ?>>
                        <option value="">Selecciona una carrera...</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>" <?php echo ($usuario['id_carrera'] == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($carrera['nombre_carrera']); ?>
                            </option>
                        <?php endforeach; ?>
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
                    <button type="submit" class="btn text-white" style="background-color: #800020;">Guardar Cambios</button>
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
            selectCarrera.value = ''; // Resetea la carrera si se cambia a profe
        }
    });
</script>

</body>
</html>