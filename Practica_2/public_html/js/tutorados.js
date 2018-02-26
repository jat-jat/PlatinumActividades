$(document).ready(function() {
    if(window.parent.sesion){
        var sesion = window.parent.sesion;
        
        sesion.onmessage = function(evt){
            try {
                var res = JSON.parse(evt.data);

                $.each(res, function (index, j) {
                    $('#ListaTutorados').append("<tr><th>" + j["mt"] +  "</th><th>" + j["ap"] + "</th><th>" + j["nb"] + "</th><th><a href='alumnos_detalles.html' onclick='Matricula(" + j["mt"] + ")'><h5>Detalles</h5></a></th></tr>");
                });
            } catch (e) {
                $('body').html("Error: " + evt.data);
            }
        };
        
        sesion.send(JSON.stringify({fn : "TUTORADOS_ver"}));
    } else {
        window.location.href = "index.html";
    }
});
    
function Matricula(Matricula){
   sessionStorage.setItem("MatriculaAlumno", Matricula);
}