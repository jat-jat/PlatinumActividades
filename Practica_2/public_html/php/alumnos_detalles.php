<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    /*if (isset($_SESSION[ID_USUARIO]) == false) {
        return;
    }*/    
    include("funciones.php");
    $conexion = getConnection();
    switch ($_POST['fn']){
        case "informacion":
            $consulta = $conexion->prepare("SELECT * FROM alumnos WHERE matricula = ?");
            $consulta->bind_param("s", $_POST['id']);
            if ($consulta->execute()){
                $res = $consulta->get_result();
                $info = $res->fetch_assoc();
                if ($info['matricula']){
                    if($info['tutor'] == $_SESSION[ID_USUARIO]){
                        echo "<label>Nombre:</label><a>"." ".$info['nombre']." ".$info['apellidos']."</a>"." "."<label>Matricula:</label><a>"." ".$info['matricula']."</a>";
                    }else{
                        lanzar_error("Usted no puede ver la informacion de este alumno");
                    }
                } else {                    
                    lanzar_error("Usuario inexistente");
                }
            }else{
                lanzar_error("Error al realizar la consulta: " . $consulta->error);
            }
        break;
        case "contenido-tabla":            
            $datos = array();
            $query = "SELECT * FROM miembros_clase m INNER JOIN clases c ON m.clase = c.id INNER JOIN materias mat ON c.materia = mat.id WHERE m.alumno = ?";
            
            if (($consulta = $conexion->prepare($query)) && $consulta->bind_param("i", $_POST["id"]) && $consulta->execute()){
                $res = $consulta->get_result();
                while($fila= $res->fetch_assoc()){
                    array_push($datos, array($fila["clase"], $fila["nombre"] . " (" . $fila["ano"] . "-" . $fila["periodo"] . ")", $fila["grupo"]));
                }
            }else{
               lanzar_error("Error al realizar la consulta: " . $consulta->error); 
            }
            
            echo json_encode($datos);
        break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>