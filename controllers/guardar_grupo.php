<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_periodo = $_POST['id_periodo'];
    $id_materia = $_POST['id_materia'];
    $id_profesor = $_POST['id_profesor'];
    $nombre_grupo = trim(strtoupper($_POST['nombre_grupo'])); 
    $cupo_maximo = $_POST['cupo_maximo'];
    
    // AHORA ESTOS SON ARRAYS (LISTAS)
    $dias = $_POST['dia_semana']; 
    $inicios = $_POST['hora_inicio'];
    $fines = $_POST['hora_fin'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $db->beginTransaction();

        // 1. Guardamos o buscamos el Grupo Principal
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

        // 2. Guardamos TODOS los horarios que el administrador agregó en el ciclo
        $query_horario = "INSERT INTO horarios (id_materia, id_profesor, id_grupo, id_periodo, cupo_maximo, dia_semana, hora_inicio, hora_fin) 
                          VALUES (:id_materia, :id_profesor, :id_grupo, :id_periodo, :cupo_maximo, :dia_semana, :hora_inicio, :hora_fin)";
        $stmt_horario = $db->prepare($query_horario);
        
        for ($i = 0; $i < count($dias); $i++) {
            $stmt_horario->execute([
                ':id_materia' => $id_materia,
                ':id_profesor' => $id_profesor,
                ':id_grupo' => $id_grupo,
                ':id_periodo' => $id_periodo,
                ':cupo_maximo' => $cupo_maximo,
                ':dia_semana' => $dias[$i],
                ':hora_inicio' => $inicios[$i],
                ':hora_fin' => $fines[$i]
            ]);
        }

        $db->commit();
        header("Location: ../views/gestion_academica.php");
        exit();

    } catch(PDOException $e) {
        $db->rollBack();
        echo "Error al aperturar el grupo múltiple: " . $e->getMessage();
    }
}
?>