<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 'Administrador' && $_SESSION['rol'] != 'Profesor')) {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';
$conexion_db = new Conexion();
$db = $conexion_db->getConnection();

$alumno = null;
$materias_inscritas = [];
$mensaje_error = "";
$horarios_disponibles = [];

if (isset($_GET['matricula']) && !empty(trim($_GET['matricula']))) {
    $matricula = trim($_GET['matricula']);
    
    $sql_alumno = "SELECT u.id_usuario, p.nombre, p.apellido_paterno AS apellidos 
                   FROM usuarios u 
                   LEFT JOIN personas p ON u.id_usuario = p.id_usuario 
                   WHERE u.matricula = :matricula LIMIT 1";
    $stmt_alumno = $db->prepare($sql_alumno);
    $stmt_alumno->execute([':matricula' => $matricula]);
    
    if ($stmt_alumno->rowCount() > 0) {
        $alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
        $id_alumno = $alumno['id_usuario'];
        
$sql_carga = "SELECT c.id_carga, c.calificacion, c.estatus_materia, h.id_grupo, m.nombre_materia AS materia, p_prof.nombre AS prof_nombre, p_prof.apellido_paterno AS prof_apellidos
                      FROM carga_academica c
                      INNER JOIN horarios h ON c.id_horario = h.id_horario
                      INNER JOIN materias m ON h.id_materia = m.id_materia
                      INNER JOIN usuarios u ON h.id_profesor = u.id_usuario
                      LEFT JOIN personas p_prof ON u.id_usuario = p_prof.id_usuario
                      WHERE c.id_alumno = :id_alumno";
        $stmt_carga = $db->prepare($sql_carga);
        $stmt_carga->execute([':id_alumno' => $id_alumno]);
        $materias_inscritas = $stmt_carga->fetchAll(PDO::FETCH_ASSOC);

$sql_materias = "SELECT h.id_horario, h.id_grupo, m.nombre_materia AS materia, p_prof.nombre AS prof_nombre, p_prof.apellido_paterno AS prof_apellidos 
                         FROM horarios h
                         INNER JOIN materias m ON h.id_materia = m.id_materia
                         INNER JOIN usuarios u ON h.id_profesor = u.id_usuario
                         LEFT JOIN personas p_prof ON u.id_usuario = p_prof.id_usuario";
        $stmt_materias = $db->prepare($sql_materias);
        $stmt_materias->execute();
        $horarios_disponibles = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $mensaje_error = "No se encontró ningún alumno con la matrícula: " . htmlspecialchars($matricula);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Académica - Tec San Pedro</title>
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
</head>
<body style="background-color: #f8f9fa;">

<div class="container-fluid p-0 d-flex flex-column flex-md-row">

    <nav class="sidebar d-none d-md-flex">
            <div class="logo_foto">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
            </div>
            <div class="menu_links">
                <a href="dashboard.php" class="item">Panel Principal</a>
                <a href="carga_academica.php" class="item">Gestión Academica</a>
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
                    <a href="gestion_academica.php" class="item">Gestión Academica</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido p-4 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="color: #800020; font-weight: bold;">Gestión Académica</h1>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="matricula" class="col-form-label fw-bold">Buscar Matrícula:</label>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="matricula" name="matricula" class="form-control" placeholder="Ej. 221000185" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn text-white" style="background-color: #800020;">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>
<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger mt-3"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<?php if ($alumno): ?>
    <div class="alert alert-success mt-3 d-flex justify-content-between align-items-center">
        <div>
            <strong>Alumno Encontrado:</strong> 
            <?php echo $alumno['nombre'] . ' ' . $alumno['apellidos']; ?> 
            (Matrícula: <?php echo htmlspecialchars($_GET['matricula']); ?>)
        </div>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAsignar">
            <i class="bi bi-plus-circle"></i> Nueva Materia
        </button>
    </div>
<?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold" style="color: #800020;">Materias Inscritas</h5>
                <button type="button" class="btn btn-sm text-white" style="background-color: #800020;" data-bs-toggle="modal" data-bs-target="#modalAsignar">
                    <i class="bi bi-plus-circle"></i> Asignar Materia
                </button>
            </div>
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead style="color: #800020;">
                        <tr>
                            <th>Materia</th>
                            <th>Profesor</th>
                            <th>Grupo</th>
                            <th>Calificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($materias_inscritas)): ?>
                            <?php foreach ($materias_inscritas as $materia): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $materia['materia']; ?></td>
                                    <td><?php echo $materia['prof_nombre'] . ' ' . $materia['prof_apellidos']; ?></td>
                                    <td><?php echo "Grupo " . $materia['id_grupo']; ?></td>
                                    <td>
                                        <?php 
                                        if ($materia['calificacion'] !== null) {
                                            echo '<span class="badge bg-success" style="font-size: 14px;">' . $materia['calificacion'] . '</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="../controllers/eliminar_carga.php?id_carga=<?php echo $materia['id_carga']; ?>&matricula=<?php echo $_GET['matricula']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de dar de baja esta materia?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <?php echo isset($_GET['matricula']) ? "Este alumno no tiene materias inscritas aún." : "Ingresa una matrícula para ver su carga académica."; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<?php if ($alumno): ?>
<div class="modal fade" id="modalAsignar" tabindex="-1" aria-labelledby="modalAsignarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #800020; color: white;">
        <h5 class="modal-title" id="modalAsignarLabel">Asignar Materia a <?php echo $alumno['nombre']; ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../controllers/guardar_carga.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="id_alumno" value="<?php echo $alumno['id_usuario']; ?>">
            <input type="hidden" name="matricula_buscada" value="<?php echo htmlspecialchars($_GET['matricula']); ?>">

            <div class="mb-3">
                <label for="id_horario" class="form-label fw-bold">Selecciona Materia y Profesor:</label>
                <select name="id_horario" id="id_horario" class="form-select" required>
                    <option value="">-- Elige una clase disponible --</option>
                    <?php 
                    if(!empty($horarios_disponibles)){
                        foreach($horarios_disponibles as $clase){
                            echo "<option value='".$clase['id_horario']."'>"
                                .$clase['materia']." - Grupo ".$clase['id_grupo']
                                ." (".$clase['prof_nombre']." ".$clase['prof_apellidos'].")</option>";
                        }
                    } else {
                        echo "<option value=''>No hay horarios registrados</option>";
                    }
                    ?>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn text-white" style="background-color: #800020;">Guardar en Carga</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>