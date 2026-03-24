<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConnection();

$rol_actual = $_SESSION['rol'];
$id_usuario_actual = $_SESSION['id_usuario'];

$mensaje_exito = "";
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_cambiar_pass'])) {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirma = $_POST['pass_confirma'];

    if ($pass_nueva !== $pass_confirma) {
        $mensaje_error = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($pass_nueva) < 6) {
        $mensaje_error = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        $stmt_pass = $db->prepare("SELECT password FROM usuarios WHERE id_usuario = :id");
        $stmt_pass->execute([':id' => $id_usuario_actual]);
        $user_db = $stmt_pass->fetch(PDO::FETCH_ASSOC);

        // Verificamos que la contraseña actual ingresada coincida con la de la BD
        if (password_verify($pass_actual, $user_db['password'])) {
            // Encriptamos la nueva y la guardamos
            $hash_nuevo = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $stmt_update = $db->prepare("UPDATE usuarios SET password = :nuevo_pass WHERE id_usuario = :id");
            
            if ($stmt_update->execute([':nuevo_pass' => $hash_nuevo, ':id' => $id_usuario_actual])) {
                $mensaje_exito = "¡Tu contraseña ha sido actualizada correctamente!";
            } else {
                $mensaje_error = "Ocurrió un error al actualizar la base de datos.";
            }
        } else {
            $mensaje_error = "La contraseña actual es incorrecta.";
        }
    }
}

$query_perfil = "SELECT u.correo, u.matricula, u.rol, p.nombre, p.apellido_paterno, p.apellido_materno, c.nombre_carrera 
                 FROM usuarios u 
                 JOIN personas p ON u.id_usuario = p.id_usuario 
                 LEFT JOIN alumnos_detalles ad ON u.id_usuario = ad.id_alumno 
                 LEFT JOIN carreras c ON ad.id_carrera = c.id_carrera 
                 WHERE u.id_usuario = :id_usuario";
$stmt_perfil = $db->prepare($query_perfil);
$stmt_perfil->execute([':id_usuario' => $id_usuario_actual]);
$datos_usuario = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Tec San Pedro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
        .icono-perfil { font-size: 5rem; color: var(--rojo-vino); }
        .main_contenido { margin-left: 0 !important; width: 100% !important; }
    </style>
</head>
<body class="bg-light">

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">
        
        <nav class="sidebar d-none d-md-flex">
            <div class="logo_foto">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
            </div>
            <div class="menu_links">
                <a href="dashboard.php" class="item">Panel Principal</a>
                <?php if ($rol_actual == 'Administrador'): ?><a href="gestion_usuarios.php" class="item">Gestión Usuarios</a><?php endif; ?>
                <a href="calificaciones.php" class="item">Calificaciones</a>
                <?php if ($rol_actual == 'Alumno' || $rol_actual == 'Administrador'): ?>
                    <a href="finanzas.php" class="item">Finanzas y Pagos</a>
                    <a href="servicio_social.php" class="item">Servicio Social</a>
                <?php endif; ?>
                <?php if ($rol_actual == 'Administrador'): ?><a href="gestion_academica.php" class="item">Gestión Académica</a><?php endif; ?>
                <?php if ($rol_actual == 'Alumno' || $rol_actual == 'Profesor'): ?><a href="kardex.php" class="item">Kardex</a><?php endif; ?>
            </div>
        </nav>

        <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="text-white fw-bold">Gestor Tec San Pedro</span>
                <div class="collapse navbar-collapse" id="menuMovil">
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="dashboard.php" class="item">Panel Principal</a>
                        <a href="calificaciones.php" class="item">Calificaciones</a>
                        <?php if ($rol_actual == 'Alumno' || $rol_actual == 'Profesor'): ?><a href="kardex.php" class="item">Kardex</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main_contenido flex-grow-1 min-vh-100">
            <header class="top_header bg-white shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: var(--rojo-vino);">
                    <i class="bi bi-person-lines-fill me-2"></i> Mi Perfil
                </h4>
                <div class="dropdown">
                    <div class="perfil dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                        <span class="fw-semibold text-secondary me-2 d-none d-sm-inline">Mi Cuenta</span>
                        <i class="bi bi-person-circle fs-3 text-secondary"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2">
                        <li><a class="dropdown-item fw-bold text-dark" href="perfil.php"><i class="bi bi-person-lines-fill me-2"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="../controllers/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </header>

            <section class="p-4">
                
                <?php if($mensaje_exito): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensaje_exito; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if($mensaje_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $mensaje_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 justify-content-center">
                    
                    <div class="col-12 col-lg-6 col-xl-5">
                        <div class="card shadow-sm border-0 h-100 borde-vino">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-info-circle-fill me-2" style="color: var(--rojo-vino);"></i>Datos Personales</h5>
                                
                                <div class="text-center mb-4">
                                    <i class="bi bi-person-circle icono-perfil"></i>
                                    <h4 class="fw-bold mt-2 mb-0"><?php echo htmlspecialchars($datos_usuario['nombre'] . ' ' . $datos_usuario['apellido_paterno']); ?></h4>
                                    <span class="badge bg-secondary mt-2 fs-6"><?php echo htmlspecialchars($datos_usuario['rol']); ?></span>
                                </div>

                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0 py-3 border-bottom text-muted">
                                        <span class="fw-bold text-dark d-block mb-1">Nombre Completo:</span>
                                        <?php echo htmlspecialchars($datos_usuario['nombre'] . ' ' . $datos_usuario['apellido_paterno'] . ' ' . $datos_usuario['apellido_materno']); ?>
                                    </li>
                                    <li class="list-group-item px-0 py-3 border-bottom text-muted">
                                        <span class="fw-bold text-dark d-block mb-1">Correo Institucional:</span>
                                        <?php echo htmlspecialchars($datos_usuario['correo']); ?>
                                    </li>
                                    
                                    <?php if ($rol_actual == 'Alumno'): ?>
                                        <li class="list-group-item px-0 py-3 border-bottom text-muted">
                                            <span class="fw-bold text-dark d-block mb-1">Matrícula:</span>
                                            <?php echo htmlspecialchars($datos_usuario['matricula']); ?>
                                        </li>
                                        <li class="list-group-item px-0 py-3 text-muted border-0">
                                            <span class="fw-bold text-dark d-block mb-1">Carrera:</span>
                                            <?php echo htmlspecialchars($datos_usuario['nombre_carrera'] ?? 'No asignada'); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-5">
                        <div class="card shadow-sm border-0 h-100 borde-vino">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-shield-lock-fill me-2" style="color: var(--rojo-vino);"></i>Cambiar Contraseña</h5>
                                
                                <p class="text-muted mb-4 small">Por tu seguridad, te recomendamos usar una contraseña de al menos 6 caracteres que no uses en otros sitios web.</p>

                                <form action="perfil.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary">Contraseña Actual</label>
                                        <input type="password" name="pass_actual" class="form-control" required placeholder="Ingresa tu contraseña actual">
                                    </div>
                                    
                                    <div class="mb-3 mt-4">
                                        <label class="form-label fw-bold text-secondary">Nueva Contraseña</label>
                                        <input type="password" name="pass_nueva" class="form-control" required placeholder="Crea una nueva contraseña">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary">Confirmar Nueva Contraseña</label>
                                        <input type="password" name="pass_confirma" class="form-control" required placeholder="Escríbela de nuevo">
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="btn_cambiar_pass" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: var(--rojo-vino);">
                                            <i class="bi bi-save-fill me-2"></i> Guardar Nueva Contraseña
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>