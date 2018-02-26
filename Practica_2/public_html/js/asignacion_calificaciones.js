var id_c = null; //id de la clase
var grupo = null;
var id_act = null;
var valor_act = null;
var seVaAModificar;

var alumnos = [];

$(document).ready(function() {
    id_c = sessionStorage.getItem("ASIG_CAL_id_clase");
    if(id_c !== null) sessionStorage.removeItem("ASIG_CAL_id_clase");
    grupo = sessionStorage.getItem("ASIG_CAL_grupo_clase");
    if(grupo !== null) sessionStorage.removeItem("ASIG_CAL_grupo_clase");
    id_act = sessionStorage.getItem("ASIG_CAL_id_act");
    if(id_act !== null) sessionStorage.removeItem("ASIG_CAL_id_act");
    seVaAModificar = (sessionStorage.getItem("ASIG_CAL_modificacion") !== null ? JSON.parse(sessionStorage.getItem("ASIG_CAL_modificacion")) : false);
    
    if(seVaAModificar){
        $("#titulo").html("Modificar las calificaciones de una actividad");
        sessionStorage.removeItem("ASIG_CAL_modificacion");
    }
    
    if(id_c === null || grupo === null || id_act == null){
        document.location.href = "clases.html";
    }
    
    if(window.parent.sesion){
        var sesion = window.parent.sesion;
        
        sesion.onmessage = function(evt){
            try {
                var res = JSON.parse(evt.data);

                $("#nb_clase").html(res["inf"][0] + " (" + res["inf"][1] + "-" + res["inf"][2] + ")");
                $("#grupo").html(grupo);

                if(res['actividades'] !== null){
                    for(var i = 0; i < 3; i++){
                        $.each(res['actividades'][i], function (index, j) {
                            if((j[3] === seVaAModificar) && j[0] == id_act){
                                $("#corte").html(i + 1);
                                $("#nb_actividad").html(j[1]);
                                valor_act = j[2];
                                $("#valor").html(valor_act + " pts.");
                            }
                        });
                    }
                }

                if(valor_act == null){
                    $('body').html("Se produjo un error al recolectar la información de la actividad.");
                    return;
                }
                alert(JSON.stringify(res));
                $.each(res['miembros'], function (index, j) {
                    alumnos.push(j["inf"][0]);

                    var fila = document.getElementById("tabla_miembros").insertRow(-1);
                    fila.insertCell(-1).innerHTML = index + 1;
                    fila.insertCell(-1).innerHTML = j["inf"][0];
                    fila.insertCell(-1).innerHTML = j["inf"][1];
                    fila.insertCell(-1).innerHTML = j["inf"][2];

                    fila.insertCell(-1).innerHTML = "<input type='number' id='calificacion_" + index + "' class='form-control' name='valor' id='valor' min='0' max='" + valor_act + "' value='0'>";
                });
            } catch (e) {
                $('body').html("Error: " + evt.data);
            }
        };
        
        sesion.send(JSON.stringify({fn : "CLASES_detalles", id_cl : id_c, id_gr : grupo}));
    } else {
        window.location.href = "index.html";
    }
});

function irADetallesClase(){
    sessionStorage.setItem("DETALLES_CLASE_id", id_c);
    sessionStorage.setItem("DETALLES_CLASE_gr", grupo);
    document.location.href = "detalles_clase.html";
}

function guardar(){
    var datos = [];
    var puntosDeAlumno = 0;
    
    for (var i = 0; i < alumnos.length; i++) {
        puntosDeAlumno = document.getElementById("calificacion_" + i).value;
        if(puntosDeAlumno >= 0 && puntosDeAlumno <= valor_act){
            datos.push([alumnos[i], puntosDeAlumno]);
        } else {
            document.getElementById("calificacion_" + i).value = 0;
            alert("El puntaje de al menos un alumno es inválido.");
            return;
        }
    }
    
    if(!confirm("Una vez guardadas, las calificaciones no se podrán modificar. ¿Desea continuar?")){
        return;
    }
    
    $(':button').prop('disabled', true);
    
    var sesion = window.parent.sesion;
    
    sesion.onmessage = function(evt){
        if(evt.data === ""){
            alert("Calificaciones guardadas correctamente.");
            irADetallesClase();
        } else {
            alert("Error: " + evt.data);
           $(':button').prop('disabled', false);
        }
    };
    
    sesion.send(JSON.stringify({fn : "CLASES_calif_actividad", id_act : id_act, calif : datos, mod : seVaAModificar}));
}