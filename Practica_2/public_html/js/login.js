$(document).ready(function() {
    //Si ya inició sesión
    if (sessionStorage.getItem("sesion") !== null){
        window.location.href = "marco.html";
    }
});

function iniciarSesion(){
    var usuario = document.getElementById("usr").value;
    var password = document.getElementById("pwd").value;    
    
    sessionStorage.setItem("urs", usuario);
    sessionStorage.setItem("pwd", password);
    
    //La sesión de debe validar en otra página, porque el websocket no se puede compartir entre páginas.
    window.location.replace("marco.html");
}