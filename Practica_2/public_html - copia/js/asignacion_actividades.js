var id = null; //id de la clase
var grupo = null;

var puntosDeCorte = [0,0,0];
var actividades = [[], [], []];

$(document).ready(function() {
    $("#btn_guardar").hide();
    
    $.post( "php/sesion.php", {fn : "comprobar"}, null, "text")
        .done(function(res) {
            if(res == 0){
                window.location.href = "index.html";
            }
        })
        .fail(function(xhr, status, error) {
            window.location.href = "index.html";
        });
    
    id = sessionStorage.getItem("ASIG_ACT_id_clase");
    if(id !== null) sessionStorage.removeItem("ASIG_ACT_id_clase");
    var titulo = sessionStorage.getItem("ASIG_ACT_titulo_clase");
    if(titulo !== null) sessionStorage.removeItem("ASIG_ACT_titulo_clase");
    grupo = sessionStorage.getItem("ASIG_ACT_grupo_clase");
    if(grupo !== null) sessionStorage.removeItem("ASIG_ACT_grupo_clase");
    
    if(id === null || titulo === null || grupo == null){
        document.location.href = "clases.html";
    }
    
    $("#nb_clase").html(titulo);
});

function capitalizar(s) {
    return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
}

function abrirVentanaCrear(){
    if(puntosDeCorte[0] >= 100 && puntosDeCorte[1] >= 100 && puntosDeCorte[2] >= 100){
        alert("Ya ha asignado todos los puntos de todos los cortes."); return;
    }
    
    document.getElementById("nombre").value = "";
    document.getElementById("valor").value = 10;
    
    for(var i = 0; i < 3; i++){
        document.getElementById("opcion_c" + (i + 1)).checked = false;
        document.getElementById("opcion_c" + (i + 1)).disabled = puntosDeCorte[i] >= 100;
    }
    
    $("#modal").modal("show");
}

function crearActividad(){
    var valor = parseInt(document.getElementById("valor").value);
    
    if(valor < 1 || valor > 100){
        alert("Valor no válido"); return;
    }
    
    var corte = null;
    for(var i = 0; i < 3; i++)
        if(document.getElementById("opcion_c" + (i + 1)).checked)
            corte = i;
    
    if(corte == null){
        alert("No ha elegido un corte."); return;
    }
    
    var nombre = capitalizar($.trim(document.getElementById("nombre").value));
    if(nombre.length < 3){
        alert("El nombre es inválido."); return;
    }
    
    if(puntosDeCorte[corte] + valor > 100){
        alert("Sobran " + (puntosDeCorte[corte] + valor - 100) + " puntos."); return;
    }
    
    puntosDeCorte[corte] += valor;
    actividades[corte].push([nombre, valor]);
    $("#c" + (corte + 1)).append("<li>" + nombre + " <i>(" + valor + "%)</i>" + "</li>");
    
    $("#progreso").html((puntosDeCorte[0] + puntosDeCorte[1] + puntosDeCorte[2]) + "/300");
    document.getElementById("progreso").style.width = Math.trunc(((puntosDeCorte[0] + puntosDeCorte[1] + puntosDeCorte[2]) / 300) * 100) + "%";
    
    if((puntosDeCorte[0] + puntosDeCorte[1] + puntosDeCorte[2]) == 300)
        $("#btn_guardar").show();
    
    $("#modal").modal("hide");
}

function reiniciar(){
    puntosDeCorte = [0,0,0];
    actividades = [[], [], []];
    for(var i = 0; i < 3; i++)
        $("#c" + (i + 1)).html("");
    
    $("#progreso").html("0/300");
    $("#progreso").css("width", "0%");
    $("#btn_guardar").hide();
}

function irADetallesClase(){
    sessionStorage.setItem("DETALLES_CLASE_id", id);
    sessionStorage.setItem("DETALLES_CLASE_gr", grupo);
    document.location.href = "detalles_clase.html";
}

function guardar(){
    $(':button').prop('disabled', true);
    
    $.ajax({
        url: "php/clases.php",
        data: {fn : "asignar_actividades", id_cl : id, act : actividades},
        type: "POST",
        dataType: 'text',
        async: false,
        success: function (respuesta) {
            alert("Actividades guardadas correctamente.");
            irADetallesClase();
        },
        error: function (xhr, status) {
            alert("Error: " + xhr.responseText);
            $(':button').prop('disabled', false);
        }
    });
}