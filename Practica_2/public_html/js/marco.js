//El WebSocket que permite hacer todas las peticiones.
var sesion;

$(document).ready(function() {
    sesion = new WebSocket("ws://" + window.location.hostname + ":8080");
    
    sesion.onopen = function(evt){
        sesion.send(JSON.stringify({
            usuario: sessionStorage.getItem("urs"),
            password: sessionStorage.getItem("pwd"),
            fn: "login"
        }));
        
        sessionStorage.removeItem("urs");
        sessionStorage.removeItem("pwd");
    };
    
    sesion.onmessage = function(evt){
        if(evt.data === ""){
            sessionStorage.setItem("sesion", 1);
            
            sesion.onmessage = function(evt){
                var info = JSON.parse(evt.data);
                if(info["error"]){
                    alert("Error al obtener el nombre del usuario: " + info["nb"]);
                } else {
                    $("#nombre").append(info["nb"]);
                }
            };
            
            sesion.send(JSON.stringify({ fn: "get_nombre" }));
        } else {
            alert(evt.data);
            sesion.close();
        }
    };

    sesion.onerror = function(evt){
        alert("Error de socket: " + evt.data);
        sesion.close();
    };
    
    sesion.onclose = function onClose(evt){
        sessionStorage.removeItem("sesion");
        window.location.replace("index.html");
    };
});

function cambiarPagina(opcionElegida, destino){
    /*if(document.getElementById("frame").src !== destino)
        document.getElementById("frame").src = destino;*/
    document.getElementById("frame").src = destino;

    var opcionesMenu = document.getElementById("menu").getElementsByTagName("li");
    for (var i = 0; i < opcionesMenu.length; ++i) {
        $(opcionesMenu[i]).removeClass("active");
    }

    $(document.getElementById(opcionElegida)).addClass("active");
}

function cerrarSesion(){
    sesion.send(JSON.stringify({ fn: "logout" }));
}