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