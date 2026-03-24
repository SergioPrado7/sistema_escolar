<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_periodo = $_POST['id_periodo'];
    $id_materia = $_POST['id_materia'];
    $id_profesor = $_POST['id_profesor'];
    $nombre_grupo = trim(strtoupper($_POST['nombre_grupo'])); 
    
    // AQUÍ ATRAPAMOS EL DÍA NUEVO
    $dia_semana = $_POST['dia_semana']; 
    
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $cupo_maximo = $_POST['cupo_maximo'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // 1. Guardamos el Grupo (El grupo no lleva día, solo las horas, eso está bien)
        $stmt_grupo = $db->prepare("SELECT id_grupo FROM grupos WHERE nombre_grupo = :nombre AND id_materia = :id_materia AND id_profesor = :id_profesor AND id_periodo = :id_periodo");
        $stmt_grupo->execute([
            ':nombre' => $nombre_grupo,
            ':id_materia' => $id_materia,
            ':id_profesor' => $id_profesor,
            ':id_periodo' => $id_periodo
        ]);
        $grupo_existente = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

        if ($grupo_existente) {
            $id_grupo = $grupo_existente['id_grupo'];
        } else {
            $stmt_nuevo_grupo = $db->prepare("INSERT INTO grupos (nombre_grupo, id_materia, id_profesor, id_periodo) VALUES (:nombre, :id_materia, :id_profesor, :id_periodo)");
            $stmt_nuevo_grupo->execute([
                ':nombre' => $nombre_grupo,
                ':id_materia' => $id_materia,
                ':id_profesor' => $id_profesor,
                ':id_periodo' => $id_periodo
            ]);
            $id_grupo = $db->lastInsertId(); 
        }

        // 2. Guardamos el Horario (¡AHORA SÍ CON EL DÍA DE LA SEMANA INCLUIDO!)
        $query_horario = "INSERT INTO horarios (id_materia, id_profesor, id_grupo, id_periodo, cupo_maximo, dia_semana, hora_inicio, hora_fin) 
                          VALUES (:id_materia, :id_profesor, :id_grupo, :id_periodo, :cupo_maximo, :dia_semana, :hora_inicio, :hora_fin)";
        $stmt_horario = $db->prepare($query_horario);
        
        $stmt_horario->execute([
            ':id_materia' => $id_materia,
            ':id_profesor' => $id_profesor,
            ':id_grupo' => $id_grupo,
            ':id_periodo' => $id_periodo,
            ':cupo_maximo' => $cupo_maximo,
            ':dia_semana' => $dia_semana, // Se manda a la base de datos
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin
        ]);

        // Regresamos a la pantalla maestra
        header("Location: ../views/gestion_academica.php");
        exit();

    } catch(PDOException $e) {
        echo "Error al aperturar el grupo: " . $e->getMessage();
    }
}
?>