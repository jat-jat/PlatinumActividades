var id_c = null; //id de la clase
var grupo = null;
var id_act = null;
var valor_act = null;

var alumnos = [];

$(document).ready(function() {
    $.post( "php/sesion.php", {fn : "comprobar"}, null, "text")
        .done(function(res) {
            if(res == 0){
                window.location.href = "index.html";
            }
        })
        .fail(function(xhr, status, error) {
            window.location.href = "index.html";
        });
    
    id_c = sessionStorage.getItem("ASIG_CAL_id_clase");
    if(id_c !== null) sessionStorage.removeItem("ASIG_CAL_id_clase");
    grupo = sessionStorage.getItem("ASIG_CAL_grupo_clase");
    if(grupo !== null) sessionStorage.removeItem("ASIG_CAL_grupo_clase");
    id_act = sessionStorage.getItem("ASIG_CAL_id_act");
    if(id_act !== null) sessionStorage.removeItem("ASIG_CAL_id_act");
    
    if(id_c === null || grupo === null || id_act == null){
        document.location.href = "clases.html";
    }
    
    $.post( "php/clases.php", {fn : "detalles", id_cl : id_c, id_gr : grupo}, null, "json")
        .done(function(res) {
            $("#nb_clase").html(res["inf"][0] + " (" + res["inf"][1] + "-" + res["inf"][2] + ")");
            $("#grupo").html(grupo);
            
            if(res['actividades'] !== null){
                for(var i = 0; i < 3; i++){
                    $.each(res['actividades'][i], function (index, j) {
                        if(!j[3] && j[0] == id_act){
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
            
            $.each(res['miembros'], function (index, j) {
                alumnos.push(j["inf"][0]);
                                
                var fila = document.getElementById("tabla_miembros").insertRow(-1);
                fila.insertCell(-1).innerHTML = index + 1;
                fila.insertCell(-1).innerHTML = j["inf"][0];
                fila.insertCell(-1).innerHTML = j["inf"][1];
                fila.insertCell(-1).innerHTML = j["inf"][2];
                
                fila.insertCell(-1).innerHTML = "<input type='number' id='calificacion_" + index + "' class='form-control' name='valor' id='valor' min='0' max='" + valor_act + "' value='0'>";
            });
        })
        .fail(function(xhr, status, error) {
            $('body').html("Error: " + xhr.responseText);
        });
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
    
    $.ajax({
        url: "php/clases.php",
        data: {fn : "calif_actividad", id_act : id_act, calif : datos},
        type: "POST",
        dataType: 'text',
        async: false,
        success: function (respuesta) {
            alert("Calificaciones guardadas correctamente.");
            irADetallesClase();
        },
        error: function (xhr, status) {
            alert("Error: " + xhr.responseText);
            $(':button').prop('disabled', false);
        }
    });
}