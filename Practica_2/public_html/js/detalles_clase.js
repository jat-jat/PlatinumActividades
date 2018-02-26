var id;
var grupo;

$(document).ready(function() {
    id = sessionStorage.getItem("DETALLES_CLASE_id");
    if(id !== null) sessionStorage.removeItem("DETALLES_CLASE_id");
    grupo = sessionStorage.getItem("DETALLES_CLASE_gr");
    if(grupo !== null) sessionStorage.removeItem("DETALLES_CLASE_gr");
    
    if(id === null || grupo === null){
        document.location.href = "clases.html";
    }
    
    if(window.parent.sesion){
        var sesion = window.parent.sesion;
        
        sesion.onmessage = function(evt){
            try {
                var res = JSON.parse(evt.data);

                $("#nb_clase").html(res["inf"][0] + " (" + res["inf"][1] + "-" + res["inf"][2] + ") - Grupo " + grupo);
            
                if(res['actividades'] !== null){
                    for(var i = 0; i < 3; i++){
                        $.each(res['actividades'][i], function (index, j) {
                            if(j[3]){
                                $("#c" + (i + 1)).append("<li><span class='glyphicon glyphicon-ok'></span><a href='javascript:calificarActividad(" + j[0] + ", true);' data-toggle='tooltip' data-placement='right' title='Modificar calificaciones'> " + j[1] + " <i>(" + j[2] + "%)</i><a/></li>");
                            } else {
                                $("#c" + (i + 1)).append("<li><span class='glyphicon glyphicon-time'></span><a href='javascript:calificarActividad(" + j[0] + ", false);' data-toggle='tooltip' data-placement='right' title='Calificar'> " + j[1] + " <i>(" + j[2] + "%)</i><a/></li>");
                            }
                        });
                    }
                    $('[data-toggle="tooltip"]').tooltip();

                } else {
                    $("#lista_actividades").replaceWith("<button class='btn btn-primary' onclick='crearActividades()'>Crear actividades</button>");
                }

                function mostrarCalificacion (calificacion){
                    if(calificacion == null)
                        return "<span class='label label-default'>-</span>";
                    else if (calificacion < 70)
                        return "<span class='label label-danger'>" + calificacion + "</span>";
                    else {
                        return "<span class='label label-success'>" + calificacion + "</span>";
                    }
                }

                $.each(res['miembros'], function (index, j) {
                    var fila = document.getElementById("tabla_miembros").insertRow(-1);
                    fila.insertCell(-1).innerHTML = index + 1;
                    fila.insertCell(-1).innerHTML = j["inf"][0];
                    fila.insertCell(-1).innerHTML = j["inf"][1];
                    fila.insertCell(-1).innerHTML = j["inf"][2];

                    fila.insertCell(-1).innerHTML = mostrarCalificacion(j["cal"][0]);
                    fila.insertCell(-1).innerHTML = mostrarCalificacion(j["cal"][1]);
                    fila.insertCell(-1).innerHTML = mostrarCalificacion(j["cal"][2]);
                });
            } catch (e) {
                $('body').html("Error: " + evt.data);
            }
        };
        
        sesion.send(JSON.stringify({fn : "CLASES_detalles", id_cl : id, id_gr : grupo}));
    } else {
        window.location.href = "index.html";
    }
});

function crearActividades(){
    sessionStorage.setItem("ASIG_ACT_id_clase", id);
    sessionStorage.setItem("ASIG_ACT_titulo_clase", $("#nb_clase").html());
    sessionStorage.setItem("ASIG_ACT_grupo_clase", grupo);
    document.location.href = "asignacion_actividades.html";
}

//Modificación: false = las calificaciones se ponen por primera vez
//true= las calificaciones se modifican
function calificarActividad(id_act, modificacion){
    sessionStorage.setItem("ASIG_CAL_id_clase", id);
    sessionStorage.setItem("ASIG_CAL_grupo_clase", grupo);
    sessionStorage.setItem("ASIG_CAL_id_act", id_act);
    sessionStorage.setItem("ASIG_CAL_modificacion", modificacion);
    document.location.href = "asignacion_calificaciones.html";
}