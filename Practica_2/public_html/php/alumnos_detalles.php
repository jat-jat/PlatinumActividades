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
            $consulta = $conexion->prepare("SELECT *FROM miembros_clase m INNER JOIN clases c ON m.clase = c.id INNER JOIN materias mat ON c.materia = mat.id WHERE m.alumno = ?");
            $consulta->bind_param("s", $_POST['id']);
            if ($consulta->execute()){
                $res = $consulta->get_result();
                while($fila= $res->fetch_assoc()){
                    echo  "<tr id='" . $fila["clase"] . "'><td>" . $fila["nombre"] . "</td><td>".$fila["ano"].$fila["periodo"]."</td>".
                           "<td>".$fila["grupo"]."</td><td>"."<a href='#body'><h5>Evento</h5></a>"."</td></tr>";
                }
            }else{
               lanzar_error("Error al realizar la consulta: " . $consulta->error); 
            }
        break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>