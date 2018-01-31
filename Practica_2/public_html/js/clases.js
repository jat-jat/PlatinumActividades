$(document).ready(function() {
    $.post( "php/clases.php", {fn : "docentes_ver"}, null, "json")
        .done(function(res) {
            $.each(res, function (index, i) {
                var fila = document.getElementById("tabla_materias").insertRow(-1);
                fila.insertCell(-1).innerHTML = i[1];
                fila.insertCell(-1).innerHTML = i[2];
                
                var colGrupos = fila.insertCell(-1);
                $.each(i[3], function (index2, j) {
                    $(colGrupos).append("<button type=\"button\" class=\"btn btn-success\" onclick=\"verDetallesClase(" + i[0] + ", \'" + j + "\')\">" + j + "</button>");
                });
                
                if(colGrupos.innerHTML == "")
                    colGrupos.innerHTML = "Ninguno";
            });
            
            if(Object.keys(res).length === 0){
                $("#tabla_materias").replaceWith("<h4>Usted no imparte ninguna clase.</h4>");
            }
        })
        .fail(function(xhr, status, error) {
            $('body').html("Error: " + xhr.responseText);
        });
});

function verDetallesClase(id, grupo){
    sessionStorage.setItem("DETALLES_CLASE_id", id);
    sessionStorage.setItem("DETALLES_CLASE_gr", grupo);
    document.location.href = "detalles_clase.html";
}