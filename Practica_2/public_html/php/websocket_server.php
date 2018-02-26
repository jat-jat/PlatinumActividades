<?php
set_time_limit(0);

//Importaciones de las clases necesarias para crear nuestro websocket.
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServerInterface;

//Importaciones para el soporte de sesiones.
use Ratchet\Session\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;

//Permite que se usen las importaciones de arriba.
require dirname(__DIR__) . '../vendor/autoload.php';

include("funciones.php");

class WebSocket implements HttpServerInterface {
    //Lista de clientes conectados.
    protected $clientes;
    //Conexión a la base de datos.
    protected $conexion_bd;
    
    public function __construct() {
        $this->clientes = new \SplObjectStorage;
        
        $this->conexion_bd = getConnection();
    }

    public function onOpen(ConnectionInterface $cliente, Psr\Http\Message\RequestInterface $request = NULL) {
        $this->clientes->attach($cliente);
    }

    public function onClose(ConnectionInterface $cliente) {
        $this->clientes->detach($cliente);
    }

    public function onMessage(ConnectionInterface $cliente, $datos) {
        $datos = json_decode($datos, true);
        
        switch ($datos['fn']) {
            case "login":
                if(!empty($datos['usuario']) && !empty($datos['password'])){
                    $consulta = $this->conexion_bd->prepare("SELECT id FROM docentes WHERE nick = ?  AND password = ?");
                    $consulta->bind_param("ss", $datos['usuario'], $datos['password']);
                    if ($consulta->execute()){
                        $res = $consulta->get_result();
                        $info = $res->fetch_assoc();
                        if ($info['id']){               
                            $cliente->Session->set("ID_USUARIO", $info['id']);
                            $cliente->send("");
                        } else {                    
                            $cliente->send("Los datos ingresados son inválidos.");
                        }
                    }else{
                        $cliente->send("Error al realizar la consulta: " . $consulta->error);                
                    }
                } else {
                    $cliente->send("Debes llenar primero todos los campos.");
                }
                break;
            case "logout":
                $cliente->Session->clear();
                $cliente->close();
                break;
            case "get_nombre":
                $nombre = array("nb" => "", "error" => false);
                
                if($cliente->Session->has("ID_USUARIO")){
                    $query = "SELECT concat(apellidos, ' ', nombre) FROM docentes WHERE id = ?";
                    if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $cliente->Session->get("ID_USUARIO")) && $consulta->execute()){
                        $res = $consulta->get_result();
                        if ($res->num_rows != 0){
                            $nombre["nb"] = $res->fetch_row()[0];
                        } else {
                            $nombre["error"] = true;
                            $nombre["nb"] = "Su cuenta ha sido eliminada.";
                        }
                    } else {
                        $nombre["error"] = true;
                        $nombre["nb"] = "Error de servidor (" . __LINE__ . ")";
                    }
                } else {
                    $nombre["error"] = true;
                    $nombre["nb"] = "No ha iniciado sesión.";
                }
                
                $cliente->send(json_encode($nombre));
                break;
            case "CLASES_docentes_ver":
                $query ="SELECT cl.id, 
                            nombre AS materia, 
                            ano, 
                            periodo 
                     FROM   (SELECT * 
                             FROM   clases 
                             WHERE  impartidor = ?) AS cl 
                            INNER JOIN materias 
                                    ON cl.materia = materias.id";
                
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $cliente->Session->get("ID_USUARIO")) && $consulta->execute()){
                    $res = $consulta->get_result();
                    $materias = array();
                    
                    while ($fila = $res->fetch_row()){
                        $tmp = array($fila[0], $fila[1], $fila[2] . "-" . $fila[3]);

                        if ($grupos = $this->conexion_bd->query("SELECT DISTINCT grupo FROM miembros_clase WHERE clase = $fila[0] ORDER BY grupo")) {
                            $tmp2 = array();
                            while ($fila2 = $grupos->fetch_row()){
                                array_push($tmp2, $fila2[0]);
                            }

                            array_push($tmp, $tmp2);
                        } else {
                            $cliente->send("Error de servidor (" . __LINE__ . ")");
                            break;
                        }

                        array_push($materias, $tmp);
                    }
                    
                    $cliente->send(json_encode($materias));
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                }
                break;
            case "CLASES_detalles":
                if(empty($datos["id_cl"]) || empty($datos["id_gr"])){
                    $cliente->send("Parámetros incorrectos");
                    break;
                }
                $datos_clase = array();

                $query ="SELECT nombre, 
                                ano, 
                                periodo 
                         FROM   (SELECT * 
                                 FROM   clases 
                                 WHERE  id = ?) AS cl 
                                INNER JOIN materias 
                                        ON cl.materia = materias.id";
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $datos["id_cl"]) && $consulta->execute()){
                    $res = $consulta->get_result();
                    if ($res->num_rows != 0){
                        $datos_clase["inf"] = $res->fetch_row();
                    } else {
                        $cliente->send("Usted no imparte esta clase.");
                        break;
                    }
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
                }


                //Cargamos las actividades
                $query = "SELECT id, nombre, valor, corte FROM actividades WHERE clase = ?";
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $datos["id_cl"]) && $consulta->execute()){
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
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
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
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("is", $datos["id_cl"], $datos["id_gr"]) && $consulta->execute()){
                    $res = $consulta->get_result();
                    while ($fila = $res->fetch_row()){
                        array_push($datos_clase["miembros"], array("inf" => $fila,
                            "cal" => ($datos_clase["actividades"] == null ? array(null, null, null) : array(0, 0, 0))));
                    }
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
                }

                if($datos_clase["actividades"] == null){
                    $cliente->send(json_encode($datos_clase));
                    break;
                }

                //Creamos el query que nos permite obtener la calificación de un alumno en una actividad específica.
                $query = "SELECT puntos FROM calificaciones WHERE actividad = ? AND alumno = ?";
                $consulta = $this->conexion_bd->prepare($query) or $cliente->send("Error de servidor (" . __LINE__ . ")");

                //Vemos si cada actividad se calificó.
                $primer_alumno = $datos_clase["miembros"][0]["inf"][0];
                $total_por_corte = array(0,0,0);
                for($corte = 0; $corte < 3; $corte++){
                    foreach($datos_clase["actividades"][$corte] as $clv_act => $actividad){                    
                        if ($tmp = $this->conexion_bd->query("SELECT * FROM calificaciones WHERE actividad = $actividad[0] AND alumno = $primer_alumno")) {
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
                                             $cliente->send("El alumno no tiene calificación en una actividad ya evaluada.");
                                             break;
                                        }
                                    } else {
                                        $cliente->send("Error de servidor (" . __LINE__ . ")");
                                        break;
                                    }
                                }
                            } else {
                                array_push($datos_clase["actividades"][$corte][$clv_act], false);
                            }
                        } else {
                            $cliente->send("Error de servidor (" . __LINE__ . ")");
                            break;
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
                        foreach($datos_clase["miembros"] as $clave => $alumno){
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

                $cliente->send(json_encode($datos_clase));
                break;
            case "CLASES_asignar_actividades":
                if(empty($datos["id_cl"]) || empty($datos["act"]) || !is_array($datos["act"])){
                    $cliente->send("Parámetros incorrectos");
                    break;
                }

                $query = "SELECT COUNT(*) FROM actividades WHERE clase = ?";
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $datos["id_cl"]) && $consulta->execute()){
                    if($consulta->get_result()->fetch_row()[0] != 0){
                        $cliente->send("Este curso ya tiene sus actividades establecidas.");
                        break;
                    }
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
                }

                $query = "INSERT INTO actividades (id, nombre, valor, clase, corte) VALUES (0, ?, ?, ?, ?)";
                if(!($consulta = $this->conexion_bd->prepare($query))){
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
                }
                iniciar_transaccion($this->conexion_bd);

                for($i = 0; $i < 3; $i++){
                    $corte = strval($i + 1);
                    $puntos_de_corte = 0;

                    foreach($datos["act"][$i] as $asignatura){
                        $asignatura[1] = intval($asignatura[1]);

                        if($asignatura[1] < 1 || $asignatura[1] > 100){
                            cerrar_transaccion($this->conexion_bd, false);
                            $cliente->send("Al menos un valor es inválido.");
                            break;
                        }
                        $puntos_de_corte += $asignatura[1];

                        if (!($consulta->bind_param("siis", $asignatura[0], $asignatura[1], $datos["id_cl"], $corte) && $consulta->execute())) {
                            cerrar_transaccion($this->conexion_bd, false);
                            $cliente->send("Error de servidor (" . __LINE__ . ")");
                            break;
                        }
                    }

                    if($puntos_de_corte != 100){
                        cerrar_transaccion($this->conexion_bd, false);
                        $cliente->send("Las actividades no completan los 100 puntos, en al menos un corte.");
                        break;
                    }
                }

                cerrar_transaccion($this->conexion_bd, true);
                $cliente->send("");
                break;
            case "CLASES_calif_actividad":
                if(empty($datos["id_act"]) || empty($datos["calif"]) || !is_array($datos["calif"])){
                    $cliente->send("Parámetros incorrectos");
                    break;
                }
                
                if(empty($datos["mod"])){
                    $query = "INSERT INTO calificaciones (puntos, actividad, alumno) VALUES (?, ?, ?)";
                } else {
                    $query = "UPDATE calificaciones SET puntos = ? WHERE actividad = ? and alumno = ?";
                }
                
                
                if(!($consulta = $this->conexion_bd->prepare($query))){
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                    break;
                }
                iniciar_transaccion($this->conexion_bd);

                foreach($datos["calif"] as $calificacion){
                    if (!($consulta->bind_param("iii", $calificacion[1], $datos["id_act"], $calificacion[0]) && $consulta->execute())) {
                        cerrar_transaccion($this->conexion_bd, false);
                        $cliente->send("Error de servidor (" . __LINE__ . ")");
                        break;
                    }
                }

                cerrar_transaccion($this->conexion_bd, true);
                $cliente->send("");
                break;
            case "TUTORADOS_ver":
                $query = "SELECT matricula, apellidos, nombre FROM alumnos where tutor = ?";
                
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $cliente->Session->get("ID_USUARIO")) && $consulta->execute()){
                    $res = $consulta->get_result();
                    $tutorados = array();
                    
                    while ($fila = $res->fetch_row()) {
                        array_push($tutorados, array("mt" => $fila[0], "ap" => $fila[1], "nb" => $fila[2]));
                    }
                    
                    $cliente->send(json_encode($tutorados));
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                }
                
                break;
            case "TUTORADOS_get_info":
                $query = "SELECT * FROM alumnos WHERE matricula = ? AND tutor = ?";
                if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("ii", $datos['id'], $cliente->Session->get("ID_USUARIO")) && $consulta->execute()){
                    $res = $consulta->get_result();
                    if($res->num_rows != 0){
                        $info = array();
                        $fila = $res->fetch_assoc();
                        
                        $info["alumno"] = array(
                            "mt" => $fila['matricula'],
                            "nb" => $fila['nombre'],
                            "ap" => $fila['apellidos']);
                        
                        $query = "SELECT * FROM miembros_clase m INNER JOIN clases c ON m.clase = c.id INNER JOIN materias mat ON c.materia = mat.id WHERE m.alumno = ?";
                        if(($consulta = $this->conexion_bd->prepare($query)) && $consulta->bind_param("i", $datos['id']) && $consulta->execute()){
                            $info["clases"] = array();
                            
                            $res = $consulta->get_result();
                            while ($fila = $res->fetch_assoc()) {
                                array_push($info["clases"], array($fila["clase"], $fila["nombre"] . " (" . $fila["ano"] . "-" . $fila["periodo"] . ")", $fila["grupo"]));
                            }
                            
                            $cliente->send(json_encode($info));
                        } else {
                            $cliente->send("Error de servidor (" . __LINE__ . ")");
                        }
                    } else {
                        $cliente->send("El alumno no es su tutorado.");
                    }
                } else {
                    $cliente->send("Error de servidor (" . __LINE__ . ")");
                }
                break;
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

}

//Inicialización del gestor de sesiones
//Hace que los objetos cliente tengan el atributo Session
$memcache = new Memcache;
$memcache->connect('localhost', 11211);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SessionProvider(
                new WebSocket,
                new Handler\MemcacheSessionHandler($memcache)
            )
        )
    ),
    8080
);
$server->run();
?>