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

// ==============================================================
// 1. LÓGICA PARA CAMBIAR LA CONTRASEÑA
// ==============================================================
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

        if (password_verify($pass_actual, $user_db['password'])) {
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

// ==============================================================
// 2. LÓGICA PARA SUBIR FOTO DE PERFIL
// ==============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_subir_foto'])) {
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['foto_perfil']['tmp_name'];
        $file_name = $_FILES['foto_perfil']['name'];
        $file_size = $_FILES['foto_perfil']['size'];
        
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($file_size < 2097152) {
                $new_file_name = "perfil_" . $id_usuario_actual . "_" . time() . "." . $file_extension;
                $upload_dir = '../assets/perfiles/';
                $dest_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    $stmt_foto = $db->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id_usuario = :id");
                    if ($stmt_foto->execute([':foto' => $new_file_name, ':id' => $id_usuario_actual])) {
                        $mensaje_exito = "¡Foto de perfil actualizada correctamente!";
                    } else {
                        $mensaje_error = "Error al actualizar la base de datos.";
                    }
                } else {
                    $mensaje_error = "Error al mover el archivo al servidor. Verifica los permisos de la carpeta.";
                }
            } else {
                $mensaje_error = "El archivo es muy pesado. El tamaño máximo es 2MB.";
            }
        } else {
            $mensaje_error = "Formato no permitido. Solo se aceptan JPG, JPEG, PNG y GIF.";
        }
    } else {
        $mensaje_error = "No se seleccionó ningún archivo o hubo un error en la subida.";
    }
}

// ==============================================================
// 3. OBTENER LOS DATOS DEL PERFIL DEL USUARIO
// ==============================================================
$query_perfil = "SELECT u.correo, u.matricula, u.rol, u.foto_perfil, p.nombre, p.apellido_paterno, p.apellido_materno, c.nombre_carrera 
                 FROM usuarios u 
                 JOIN personas p ON u.id_usuario = p.id_usuario 
                 LEFT JOIN alumnos_detalles ad ON u.id_usuario = ad.id_alumno 
                 LEFT JOIN carreras c ON ad.id_carrera = c.id_carrera 
                 WHERE u.id_usuario = :id_usuario";
$stmt_perfil = $db->prepare($query_perfil);
$stmt_perfil->execute([':id_usuario' => $id_usuario_actual]);
$datos_usuario = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

// Determinar si tiene foto válida o usamos el ícono
$tiene_foto = false;
$foto_mostrar = "";
if (!empty($datos_usuario['foto_perfil']) && $datos_usuario['foto_perfil'] !== 'default.png') {
    $tiene_foto = true;
    $foto_mostrar = '../assets/perfiles/' . $datos_usuario['foto_perfil'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Tec San Pedro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../assets/iconos/perfilIcono.ico">
    <link rel="stylesheet" href="../styles/estilo.css">
    <style>
        .borde-vino { border-left: 5px solid var(--rojo-vino) !important; }
        
        .foto-perfil-img { 
            width: 150px; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 4px solid var(--rojo-vino);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .icono-perfil-gigante {
            font-size: 7rem;
            color: var(--rojo-vino);
            line-height: 1;
            display: inline-block;
        }

        .btn-subir {
            background-color: var(--rojo-vino);
            color: white;
            font-weight: bold;
        }
        .btn-subir:hover { background-color: #5c0017; color:white; }
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
            <?php if ($_SESSION['rol'] == 'Administrador'): ?><a href="gestion_usuarios.php" class="item">Gestión Usuarios</a><?php endif; ?>
            <a href="horarios.php" class="item">Horarios</a>
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
            <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?>
                <a href="kardex.php" class="item">Kardex</a>
            <?php endif; ?>
        </div>
    </nav>

    <nav class="navbar navbar-dark d-md-none p-3 w-100" style="background-color: var(--rojo-vino) !important; z-index: 1000;">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMovil">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="text-white fw-bold">Mi Perfil</span>
            <div class="collapse navbar-collapse" id="menuMovil">
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="dashboard.php" class="item">Panel Principal</a>
                    <?php if ($_SESSION['rol'] == 'Administrador'): ?><a href="gestion_usuarios.php" class="item">Gestión Usuarios</a><?php endif; ?>
                    <a href="horarios.php" class="item">Horarios</a>
                    <a href="calificaciones.php" class="item">Calificaciones</a>
                    <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?><a href="finanzas.php" class="item">Finanzas y Pagos</a><?php endif; ?>
                    <?php if ($_SESSION['rol'] == 'Administrador'): ?><a href="gestion_academica.php" class="item">Gestión Académica</a><?php endif; ?>
                    <?php if ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Alumno'): ?><a href="servicio_social.php" class="item">Servicio Social</a><?php endif; ?>
                    <?php if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Profesor'): ?><a href="kardex.php" class="item">Kardex</a><?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="main_contenido">
        <div class="p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="color: var(--rojo-vino); font-weight: bold;"><i class="bi bi-person-lines-fill me-2"></i> Mi Perfil</h1>
                <a href="../controllers/logout.php" class="btn btn-outline-danger shadow-sm fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
                </a>
            </div>

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
                                <?php if ($tiene_foto): ?>
                                    <img src="<?php echo $foto_mostrar; ?>" alt="Foto de Perfil" class="foto-perfil-img mb-3">
                                <?php else: ?>
                                    <i class="bi bi-person-circle icono-perfil-gigante mb-3"></i>
                                <?php endif; ?>
                                
                                <h4 class="fw-bold mt-2 mb-0"><?php echo htmlspecialchars($datos_usuario['nombre'] . ' ' . $datos_usuario['apellido_paterno']); ?></h4>
                                <span class="badge bg-secondary mt-2 fs-6"><?php echo htmlspecialchars($datos_usuario['rol']); ?></span>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFoto">
                                        <i class="bi bi-camera-fill me-1"></i> <?php echo $tiene_foto ? 'Cambiar Foto' : 'Subir Foto'; ?>
                                    </button>
                                </div>
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
                                    <button type="submit" name="btn_cambiar_pass" class="btn text-white fw-bold py-2 shadow-sm btn-subir">
                                        <i class="bi bi-save-fill me-2"></i> Guardar Nueva Contraseña
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--rojo-vino);">
                <h5 class="modal-title fw-bold"><i class="bi bi-image me-2"></i>Actualizar Foto de Perfil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="perfil.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 text-center">
                    <p class="text-muted small mb-4">Selecciona una imagen desde tu dispositivo. Formatos permitidos: JPG, PNG. Peso máximo: 2MB.</p>
                    
                    <div class="mb-3">
                        <input class="form-control" type="file" name="foto_perfil" id="foto_perfil" accept=".jpg, .jpeg, .png, .gif" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="btn_subir_foto" class="btn text-white fw-bold btn-subir">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Subir Foto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>