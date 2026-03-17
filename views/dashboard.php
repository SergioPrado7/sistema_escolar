<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="icon" type="image/x-icon" href="../assets/iconos/gestionIcono.ico">
    <link rel="stylesheet" href="../styles/estilo.css">
</head>
<body>

    <nav class="sidebar">
        <div class="logo_foto">
            <img style="display: block; margin: 0 auto; width:185px; height:95px;" src="../assets/logos/logoPrincipalLogin.png" alt="Logo Tec">
            </div>
        <div class="menu_links">
        <a href="dashboard.php" class="item">Panel Principal</a>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
        <a href="gestion_usuarios.php" class="item">Gestión Usuarios</a>
        <?php endif; ?>
        <a href="#" class="item">Mis Calificaciones</a>
        <a href="#" class="item">Finanzas y Pagos</a>
        <a href="#" class="item">Carga Academica</a>
        <a href="#" class="item">Servicio Social</a>
        </div>
    </nav>

    <main class="main_contenido">
        
        <header class="top_header">
            <h1 class="titulo" style="display: block; margin: 0 auto;">Gestor de alumnos</h1>
            <div class="perfil">
                <span class="perfil_texto">Mi Perfil</span>
                <div class="perfil_foto"></div>
            </div>
        </header>

        <section class="dashboard">
            <h1 class="seccion_titulo">Materias</h1>
            <div class="materias_grid">
            <div class="tarjeta_materia"></div>
            <div class="tarjeta_materia"></div>
            <div class="tarjeta_materia"></div>
            <div class="tarjeta_materia"></div>
            <div class="tarjeta_materia"></div>
            </div>
        </section>

    </main>

</body>
</html>