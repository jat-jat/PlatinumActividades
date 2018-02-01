<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    $conexion = getConnection();
    
    if(empty($_SESSION[ID_USUARIO])){
        lanzar_error("No ha iniciado sesión.");
    }
    
    switch ($_POST['fn']){
        case "docentes_ver":
            $query ="SELECT cl.id, 
                            nombre AS materia, 
                            ano, 
                            periodo 
                     FROM   (SELECT * 
                             FROM   clases 
                             WHERE  impartidor = ?) AS cl 
                            INNER JOIN materias 
                                    ON cl.materia = materias.id";
            
            $materias = array();
            
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("i", $_SESSION[ID_USUARIO]) && $consulta->execute()){
                $res = $consulta->get_result();
                
                while ($fila = $res->fetch_row()){
                    $tmp = array($fila[0], $fila[1], $fila[2] . "-" . $fila[3]);
                    
                    if ($grupos = $conexion->query("SELECT DISTINCT grupo FROM miembros_clase WHERE clase = $fila[0] ORDER BY grupo")) {
                        $tmp2 = array();
                        while ($fila2 = $grupos->fetch_row()){
                            array_push($tmp2, $fila2[0]);
                        }
                        
                        array_push($tmp, $tmp2);
                    } else {
                        lanzar_error("Error de servidor (" . __LINE__ . ")");
                    }
                    
                    array_push($materias, $tmp);
                }
            } else {
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            
            echo json_encode($materias);
            break;
        case "detalles":
            if(empty($_POST["id_cl"]) || empty($_POST["id_gr"])){
                lanzar_error("Parámetros incorrectos");
            }
            $datos_clase = array();
            
            $query ="SELECT nombre, 
                            ano, 
                            periodo 
                     FROM   (SELECT * 
                             FROM   clases 
                             WHERE  id = ? AND impartidor = ?) AS cl 
                            INNER JOIN materias 
                                    ON cl.materia = materias.id";
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("ii", $_POST["id_cl"], $_SESSION[ID_USUARIO]) && $consulta->execute()){
                $res = $consulta->get_result();
                if ($res->num_rows != 0){
                    $datos_clase["inf"] = $res->fetch_row();
                } else {
                    lanzar_error("Usted no imparte esta clase.");
                }
            } else {
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            
            
            //Cargamos las actividades
            $query = "SELECT id, nombre, valor, corte FROM actividades WHERE clase = ?";
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("i", $_POST["id_cl"]) && $consulta->execute()){
                $res = $consulta->get_result();
                if ($res->num_rows != 0){
                    $datos_clase["actividades"] = array(array(), array(), array());
                    
                    while ($fila = $res->fetch_row()){
                        array_push($datos_clase["actividades"][intval($fila[3]) - 1], array($fila[0], $fila[1], $fila[2]));
                    }
                } else {
                    $datos_clase["actividades"] = null;
                }
            } else {
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            
            //Cargamos a los alumnos
            $datos_clase["miembros"] = array();
            $query = "SELECT matricula, 
                            apellidos, 
                            nombre 
                     FROM   (SELECT alumno 
                             FROM   miembros_clase 
                             WHERE  clase = ? 
                                    AND grupo = ?) AS mat 
                            INNER JOIN alumnos 
                                    ON mat.alumno = alumnos.matricula
                            ORDER BY apellidos";
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("is", $_POST["id_cl"], $_POST["id_gr"]) && $consulta->execute()){
                $res = $consulta->get_result();
                while ($fila = $res->fetch_row()){
                    array_push($datos_clase["miembros"], array("inf" => $fila,
                        "cal" => ($datos_clase["actividades"] == null ? array(null, null, null) : array(0, 0, 0))));
                }
            } else {
                lanzar_error("Error de servidor (" . __LINE__ . ")", false);
            }
            
            if($datos_clase["actividades"] == null){
                echo json_encode($datos_clase);
                break;
            }
            
            //Creamos el query que nos permite obtener la calificación de un alumno en una actividad específica.
            $query = "SELECT puntos FROM calificaciones WHERE actividad = ? AND alumno = ?";
            $consulta = $conexion->prepare($query) or lanzar_error("Error de servidor (" . __LINE__ . ")");
            
            //Vemos si cada actividad se calificó.
            $primer_alumno = $datos_clase["miembros"][0]["inf"][0];
            $total_por_corte = array(0,0,0);
            for($corte = 0; $corte < 3; $corte++){
                foreach($datos_clase["actividades"][$corte] as $clv_act => $actividad){                    
                    if ($tmp = $conexion->query("SELECT * FROM calificaciones WHERE actividad = $actividad[0] AND alumno = $primer_alumno")) {
                        if($tmp->num_rows != 0){
                            array_push($datos_clase["actividades"][$corte][$clv_act], true);
                            $total_por_corte[$corte] += $actividad[2];
                            $minimo_aprobatorio = $actividad[2] * 0.7;
                            
                            //Añadimos la calificación que cada alumno obtuvo en esta actividad.
                            foreach($datos_clase["miembros"] as $clave => $alumno){
                                if($consulta->bind_param("ii", $actividad[0], $alumno["inf"][0]) && $consulta->execute()){
                                    $res = $consulta->get_result();
                                    if($res->num_rows != 0){
                                        $fila = $res->fetch_row();
                                        $datos_clase["miembros"][$clave]["cal"][$corte] += $fila[0];
                                        
                                        if($fila[0] < $minimo_aprobatorio){
                                            $datos_clase["miembros"][$clave]["reprobo_una_actividad"][$corte] = 1;
                                        }
                                    } else {
                                         lanzar_error("El alumno no tiene calificación en una actividad ya evaluada.");
                                    }
                                } else {
                                    lanzar_error("Error de servidor (" . __LINE__ . ")", false);
                                }
                            }
                        } else {
                            array_push($datos_clase["actividades"][$corte][$clv_act], false);
                        }
                    } else {
                        lanzar_error("Error de servidor (" . __LINE__ . ")");
                    }
                }
            }
            unset($primer_alumno);
            
            for($corte = 0; $corte < 3; $corte++){
                if($total_por_corte[$corte] == 0){
                    foreach($datos_clase["miembros"] as $clave => $alumno){
                        $datos_clase["miembros"][$clave]["cal"][$corte] = null;
                    }
                } else if($total_por_corte[$corte] != 100){
                    foreach($datos_clase["miembros"] as $alumno){
                        //Aplicamos una regla de 3, para crear una nueva calificación, en caso de que no se hayan
                        //calificado todas.
                        $datos_clase["miembros"][$clave]["cal"][$corte] = round(($alumno["cal"][$corte] * 100) / $total_por_corte[$corte], 2);
                    }
                }
                
                foreach($datos_clase["miembros"] as $clave => $alumno){
                    if($datos_clase["miembros"][$clave]["cal"][$corte] == null){
                        continue;
                    }
                    
                    if($datos_clase["miembros"][$clave]["cal"][$corte] < 10){
                        $datos_clase["miembros"][$clave]["cal"][$corte] = 10;
                    } else if($datos_clase["miembros"][$clave]["cal"][$corte] >= 70 && !empty($datos_clase["miembros"][$clave]["reprobo_una_actividad"][$corte])){
                        $datos_clase["miembros"][$clave]["cal"][$corte] = 69;
                    }
                }
            }
            foreach($datos_clase["miembros"] as $clave => $alumno){
                unset($datos_clase["miembros"][$clave]["reprobo_una_actividad"]);
            }
            
            echo json_encode($datos_clase);
            break;
        case "asignar_actividades":
            if(empty($_POST["id_cl"]) || empty($_POST["act"]) || !is_array($_POST["act"])){
                lanzar_error("Parámetros incorrectos");
            }
            
            $query = "SELECT COUNT(*) FROM actividades WHERE clase = ?";
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("i", $_POST["id_cl"]) && $consulta->execute()){
                if($consulta->get_result()->fetch_row()[0] != 0){
                    lanzar_error("Este curso ya tiene sus actividades establecidas.");
                }
            } else {
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            
            $query = "INSERT INTO actividades (id, nombre, valor, clase, corte) VALUES (0, ?, ?, ?, ?)";
            if(!($consulta = $conexion->prepare($query))){
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            iniciar_transaccion($conexion);
            
            for($i = 0; $i < 3; $i++){
                $corte = strval($i + 1);
                $puntos_de_corte = 0;
                
                foreach($_POST["act"][$i] as $asignatura){
                    if(!is_int($asignatura[1]) || $asignatura[1] < 1 || $asignatura[1] > 100){
                        cerrar_transaccion($conexion, false);
                        lanzar_error("Al menos un valor es inválido.");
                    }
                    $puntos_de_corte += $asignatura[1];
                    
                    if (!($consulta->bind_param("siis", $asignatura[0], $asignatura[1], $_POST["id_cl"], $corte) && $consulta->execute())) {
                        cerrar_transaccion($conexion, false);
                        lanzar_error("Error de servidor (" . __LINE__ . ")");
                    }
                }
                
                if($puntos_de_corte != 100){
                    cerrar_transaccion($conexion, false);
                    lanzar_error("Las actividades no completan los 100 puntos, en al menos un corte.");
                }
            }
            
            cerrar_transaccion($conexion, true);
            break;
        case "calif_actividad":
            if(empty($_POST["id_act"]) || empty($_POST["calif"]) || !is_array($_POST["calif"])){
                lanzar_error("Parámetros incorrectos");
            }
            
            $query = "INSERT INTO calificaciones (actividad, alumno, puntos) VALUES (?, ?, ?)";
            if(!($consulta = $conexion->prepare($query))){
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
            iniciar_transaccion($conexion);
            
            foreach($_POST["calif"] as $calificacion){
                if (!($consulta->bind_param("iii", $_POST["id_act"], $calificacion[0], $calificacion[1]) && $consulta->execute())) {
                    cerrar_transaccion($conexion, false);
                    lanzar_error("Error de servidor (" . __LINE__ . ")");
                }
            }
            
            cerrar_transaccion($conexion, true);
            break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>