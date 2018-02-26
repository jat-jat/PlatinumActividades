<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    
    
    switch ($_POST['fn']){
        case "ver":
            $sql = sprintf("SELECT * FROM alumnos where tutor=" . $_SESSION[ID_USUARIO]);
        $conexion = getConnection();
        $resultado = $conexion->query($sql);
        $info = array();
        while ($row = $resultado->fetch_assoc()) {
            $info[] = $row;
        }
        foreach ($info as $info2) {
            echo "<tr><th>" . $info2["matricula"] . "</th>" . "<th>" . $info2["apellidos"] . "</th>" . "<th>" . $info2["nombre"] . "</th><th><a href='alumnos_detalles.html' onclick='Matricula(" . $info2["matricula"] . ")'><h5>Detalles</h5></a></th></tr>";
        }
        //Devuelve los tutorados del maestro que tenga iniciada su sesión en el momento.
            break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>