<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    
    switch ($_POST['fn']){
        case "login":
            
            break;
        case "logout":
            
            break;
        case "comprobar":
            //Si el usuario inició sesión se devuelve un 1, en caso contrario, 0.
            if(empty($_SESSION[ID_USUARIO])){
                echo "0";
            } else {
                echo "1";
            }
            break;
        default:
            lanzar_error("Error de servidor (" . __LINE__ . ")", false);
    }
?>