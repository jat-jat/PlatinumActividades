$(document).ready(function() {
    $.post( "php/sesion.php", {fn : "comprobar"}, null, "text")
        .done(function(res) {
            if(res == 1){
                window.location.href = "marco.html";
            }
        })
        .fail(function(xhr, status, error) {
            window.location.href = "index.html";
        });
});

function iniciarSesion(){
    var usuario = document.getElementById("usr").value;
    var password = document.getElementById("pwd").value;    
    $.ajax({
        url: "php/sesion.php",
        data: {
            usuario: usuario,
            password:password, 
            fn:"login"
        },
        type: "POST",
        datatype: "text",        
        success: function(respuesta) {            
            window.location.replace("marco.html");
        },
        error: function(xhr, textStatus) {          
            alert(xhr.responseText);
        }
    });
}