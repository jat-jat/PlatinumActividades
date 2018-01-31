<?php
    /**
     * Esta función se ejecuta automáticamente al terminar la ejecución del PHP.
     * Si se suscitó un WARNING (como una variable no declarada), se lanza un error.
     */
    function checar_error(){
        $last_error = error_get_last();
        if ($last_error && ($last_error['type'] == E_ERROR && $last_error['type'] == E_NOTICE)) {
            //ob_end_clean();
            lanzar_error("Error de servidor.", false);
        }
    }
    register_shutdown_function('checar_error');
    
    /**
     * Permite lanzar un error de PHP, haciendo que se devuelva el error #500.
     * @param string $texto Un texto explicando el problema.
     * @param bool $die Si la ejecución de este PHP se cancela (se mata) después de lanzar el error.
     */
    function lanzar_error($texto, $die = true){
        //Hacemos que se devuelva el error 500.
        http_response_code(500);
        echo $texto;
        //Terminamos la ejecución del php si es necesario.
        if($die) die();
    }
    
    /**
     * Abre la sesión si es necesario.
     */
    if(!isset($_SESSION)) { 
        session_start(); 
    } 
    
    /**
     * Crea una conexión con la base de datos y la devuelve.
     * @return mysqli Objeto de la clase MYSQLi
     */
    function getConnection(){
        $mysqli = new mysqli("localhost", "root", "456123", "platinum");
        if ($mysqli->connect_errno) {
                lanzar_error("Error al conectar con la base de datos: " . $mysqli->connect_error);
        }
        return $mysqli;
    }
    
    function iniciar_transaccion($mysqli){
        $mysqli->autocommit(FALSE);
    }
    
    function cerrar_transaccion($mysqli, $guardar_cambios){
        if($guardar_cambios){
            if(!$mysqli->commit()){
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
        } else {
            if(!$mysqli->rollback()){
                lanzar_error("Error de servidor (" . __LINE__ . ")");
            }
        }
        $mysqli->autocommit(TRUE);
    }
    
    define("ID_USUARIO", "usr");
?>