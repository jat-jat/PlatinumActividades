<?php
    if(empty($_POST['fn'])){
        $_POST['fn'] = " ";
    }
    
    include("funciones.php");
    $conexion = getConnection();
    switch ($_POST['fn']){
        case "login":
            if(empty($_POST['usuario']) == false && empty($_POST['password']) == false){
                $consulta = $conexion->prepare("SELECT id FROM docentes WHERE nick = ?  AND password = ?");
                $consulta->bind_param("ss", $_POST['usuario'], $_POST['password']);
                if ($consulta->execute()){
                    $res = $consulta->get_result();
                    $info = $res->fetch_assoc();
                    if ($info['id']){                       
                        $_SESSION[ID_USUARIO] = $info['id'];
                    } else {                    
                        lanzar_error("Los datos ingresados son invalidos");
                    }
                }else{
                    lanzar_error("Error al realizar la consulta: " . $consulta->error);                
                }
            }else{
                lanzar_error("Debes llenar primero todos los campos");
            }
        break;
        case "logout":  
            if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
            // destruimos la SESSION.
            session_destroy();
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