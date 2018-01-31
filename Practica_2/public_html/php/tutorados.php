<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    
    switch ($_POST['fn']){
        case "ver":
            //Devuelve los tutorados del maestro que tenga iniciada su sesión en el momento.
            break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>