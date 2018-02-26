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