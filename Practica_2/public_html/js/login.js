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
            alert("Incio de sesion realizado con exito");
        },
        error: function(xhr, textStatus) {          
            alert(xhr.responseText);
        }
    });
}