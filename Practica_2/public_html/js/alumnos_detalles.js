var matricula = null;

$(document).ready(function() {
    matricula = sessionStorage.getItem("MatriculaAlumno");
    if(matricula !== null)
        sessionStorage.removeItem("MatriculaAlumno");
    else
        document.location.href = "tutorados.html";
    
    if(window.parent.sesion){
        var sesion = window.parent.sesion;
        
        sesion.onmessage = function(evt){
            try {
                var res = JSON.parse(evt.data);

                $('#informacion_alumno').empty();
                $('#informacion_alumno').append("<label>Nombre:</label><a> " + res['alumno']['nb'] + " " + res['alumno']['ap'] + "</a> <label>Matricula:</label><a> " + res['alumno']['mt'] + "</a>");
                
                mostrarClases(res['clases'], 0);
            } catch (e) {
                $('body').html("Error: " + evt.data);
            }
        };
        
        sesion.send(JSON.stringify({fn : "TUTORADOS_get_info", id: matricula}));
    } else {
        window.location.href = "index.html";
    }
});

function mostrarCalificacion (calificacion){
    if(calificacion == null)
        return "<span class='label label-default'>-</span>";
    else if (calificacion < 70)
        return "<span class='label label-danger'>" + calificacion + "</span>";
    else {
        return "<span class='label label-success'>" + calificacion + "</span>";
    }
}

function mostrarClases(clases, i){
    if(i === clases.length){
        return;
    }
    
    var fila = document.getElementById("tabla").insertRow(-1);
    fila.insertCell(-1).innerHTML = clases[i][1];
    fila.insertCell(-1).innerHTML = clases[i][2];
    
    var sesion = window.parent.sesion;
    
    sesion.onmessage = function(evt2){
        try {
            $.each(JSON.parse(evt2.data)['miembros'], function (index, k) {
                if(k["inf"][0] == matricula){
                    fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][0]);
                    fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][1]);
                    fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][2]);
                }
            });
            mostrarClases(clases, i + 1);
        } catch (e) {
            fila.insertCell(-1).innerHTML = "Error";
            alert(evt2.data);
        }
    };
    
    sesion.send(JSON.stringify({fn : "CLASES_detalles", id_cl : clases[i][0], id_gr : clases[i][2]}));
}