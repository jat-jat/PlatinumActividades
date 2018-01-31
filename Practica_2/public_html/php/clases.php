<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    $conexion = getConnection();
    $id = 1;
    
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
            
            if(($consulta = $conexion->prepare($query)) && $consulta->bind_param("i", $id) && $consulta->execute()){
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
            
            break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>