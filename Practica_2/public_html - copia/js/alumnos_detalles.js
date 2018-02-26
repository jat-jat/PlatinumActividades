var matricula = null;

$(document).ready(function() {
    obtenerDetallesAlumno();
});

function obtenerDetallesAlumno(){
    matricula = sessionStorage.getItem("MatriculaAlumno");
    if(matricula !== null) sessionStorage.removeItem("MatriculaAlumno");
    
    if(matricula === null){
        document.location.href = "tutorados.html";
    }
    
    $.ajax({
        url: "php/alumnos_detalles.php",
        data: {
            id: matricula,
            fn: "informacion"
        },
        type: "POST",
        datatype: "text",
        success: function (resultado) {
            $('#informacion_alumno').empty();
            $('#informacion_alumno').append(resultado);
            llenarTabla(matricula);
        },
        error: function (xhr, textStatus) {
            alert(xhr.responseText);
        }
    });
}

function llenarTabla(id){
    $.ajax({
        url: "php/alumnos_detalles.php",
        data:{            
            id:id,
            fn:"contenido-tabla"
        },
        type: "POST",
        dataType: "json",
        async: false,
        success: function(res){
            $.each(res, function (index, j) {
                var fila = document.getElementById("tabla").insertRow(-1);
                fila.insertCell(-1).innerHTML = j[1];
                fila.insertCell(-1).innerHTML = j[2];
                
                function mostrarCalificacion (calificacion){
                    if(calificacion == null)
                        return "<span class='label label-default'>-</span>";
                    else if (calificacion < 70)
                        return "<span class='label label-danger'>" + calificacion + "</span>";
                    else {
                        return "<span class='label label-success'>" + calificacion + "</span>";
                    }
                }
                
                $.ajax({
                    url: "php/clases.php",
                    data:{fn : "detalles", id_cl : j[0], id_gr : j[2]},
                    type: "POST",
                    dataType: "json",
                    async: false,
                    success: function(res2){
                        $.each(res2['miembros'], function (index, k) {
                            if(k["inf"][0] == matricula){
                                fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][0]);
                                fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][1]);
                                fila.insertCell(-1).innerHTML = mostrarCalificacion(k["cal"][2]);
                            }
                        });
                    },
                    error: function(xhr, textStatus) {
                       fila.insertCell(-1).innerHTML = "Error";
                       alert(xhr.responseText);
                    }
                });
            });         
        },
        error: function(xhr, textStatus) {
           alert(xhr.responseText);
        }
    });
}