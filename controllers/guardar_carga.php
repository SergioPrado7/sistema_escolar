<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alumno = $_POST['id_alumno'];
    $id_horario = $_POST['id_horario'];
    $matricula = $_POST['matricula_buscada'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // 1. Obtener la materia y el cupo del grupo seleccionado
        $stmt_info = $db->prepare("SELECT id_materia, cupo_maximo FROM horarios WHERE id_horario = :id_horario");
        $stmt_info->execute([':id_horario' => $id_horario]);
        $info_grupo = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info_grupo) {
            die("Error: El grupo seleccionado no existe.");
        }

        $id_materia = $info_grupo['id_materia'];
        $cupo_maximo = $info_grupo['cupo_maximo'];

        // 2. VALIDACIÓN: ¿El alumno ya está cursando ESTA MATERIA en algún otro grupo activo?
        $stmt_check_materia = $db->prepare("
            SELECT ca.id_carga 
            FROM carga_academica ca 
            INNER JOIN horarios h ON ca.id_horario = h.id_horario 
            WHERE ca.id_alumno = :id_alumno 
            AND h.id_materia = :id_materia 
            AND ca.finalizado = 0
        ");
        $stmt_check_materia->execute([':id_alumno' => $id_alumno, ':id_materia' => $id_materia]);
        
        if ($stmt_check_materia->fetch()) {
            echo "<script>
                    alert('¡Operación denegada! El alumno ya está cursando esta materia en este u otro grupo.');
                    window.location.href = '../views/gestion_academica.php?matricula=" . urlencode($matricula) . "';
                  </script>";
            exit();
        }

        // 3. VALIDACIÓN: Revisar si el grupo ya está lleno (Cupo Máximo)
        $stmt_cupo = $db->prepare("SELECT COUNT(*) as inscritos FROM carga_academica WHERE id_horario = :id_horario");
        $stmt_cupo->execute([':id_horario' => $id_horario]);
        $total_inscritos = $stmt_cupo->fetch(PDO::FETCH_ASSOC)['inscritos'];

        if ($total_inscritos >= $cupo_maximo) {
            echo "<script>
                    alert('¡Cupo Lleno! Este grupo ya ha alcanzado su límite de " . $cupo_maximo . " alumnos.');
                    window.location.href = '../views/gestion_academica.php?matricula=" . urlencode($matricula) . "';
                  </script>";
            exit();
        }

        // 4. Si pasó todos los filtros de seguridad, lo inscribimos
        $stmt_insert = $db->prepare("INSERT INTO carga_academica (id_alumno, id_horario) VALUES (:id_alumno, :id_horario)");
        $stmt_insert->execute([':id_alumno' => $id_alumno, ':id_horario' => $id_horario]);

        // Lo regresamos a la pantalla de gestión, cargando su matrícula automáticamente
        header("Location: ../views/gestion_academica.php?matricula=" . urlencode($matricula));
        exit();

    } catch(PDOException $e) {
        echo "Error en la base de datos al asignar la materia: " . $e->getMessage();
    }
}
?>