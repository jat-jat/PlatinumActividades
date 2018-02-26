$(document).ready(function() {
    $.post( "php/sesion.php", {fn : "get_nombre"}, null, "text")
        .done(function(res) {
            $("#nombre").append(res);
        })
        .fail(function(xhr, status, error) {
            alert("Error: " + xhr.responseText);
            //window.location.href = "index.html";
        });
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
    $.ajax({
        url: "php/sesion.php",
        data:{            
            fn:"logout"
        },
        type: "POST",
        datatype: "text",        
        success: function(respuesta) {            
            window.location.replace("index.html");
        },
        error: function(xhr, textStatus) {          
            alert(xhr.responseText);
        }
    });
}