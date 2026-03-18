<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Gestor de Alumnos</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../styles/estilo.css">
    
    <link rel="icon" type="image/x-icon" href="../assets/iconos/gestionIcono.ico">
</head>
<body>

    <div class="container-fluid p-0 d-flex flex-column flex-md-row">
        
        <nav class="sidebar d-none d-md-flex">
            <div class="logo_foto">
                <img src="../assets/logos/logoPrincipalLogin.png" alt="Logo" style="max-width: 180px; height: auto;">
            </div>
            <div class="menu_links">
                <a href="dashboard.php" class="item">Panel Principal</a>
                <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
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
                <span class="text-white fw-bold">Gestor Tec San Pedro</span>
                
                <div class="collapse navbar-collapse" id="menuMovil">
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="dashboard.php" class="item">Panel Principal</a>
                        <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
                        <a href="#" class="item">Mis Calificaciones</a>
                        <a href="#" class="item">Finanzas y Pagos</a>
                        <a href="#" class="item">Carga Academica</a>
                        <a href="#" class="item">Servicio Social</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main_contenido">
            <header class="top_header">
                <h1 class="titulo text-center flex-grow-1">Gestor Tec San Pedro</h1>
                
                <div class="perfil">
                    <span class="perfil_texto">Mi Perfil</span>
                    <div class="perfil_foto"></div>
                </div>
            </header>

            <section class="dashboard">
                <h1 class="seccion_titulo">Materias Inscritas</h1>
                
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="tarjeta_materia"></div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="tarjeta_materia"></div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="tarjeta_materia"></div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="tarjeta_materia"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>