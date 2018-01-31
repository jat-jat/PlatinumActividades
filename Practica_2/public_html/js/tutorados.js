$(document).ready(function() {
       Tutorados();   
    });
 
    function Tutorados(){
        
          $.ajax({
        url: "php/sesion.php",
        data:{
            fn:"comprobar",
        },
        type: "POST",
        datatype: "text",
        async:true,
        success: function(resultado) {
            if(resultado==0)
           window.location.replace("index.html");
        },
        error: function(XHR, textStatus) {
           alert("incorrecto prro: "+XHR.responseText);
        }
    });

         $.ajax({
        url: "php/tutorados.php",
        data:{
            fn:"ver",
        },
        type: "POST",
        datatype: "text",
       
        beforeSend: function (xhr) {
            
        },
        success: function(resultado) {
          //  $('#ListaTutorados').empty(); //Vaciamos el contenido de la tabla
            $('#ListaTutorados').append(resultado);
             
        },
        error: function(XHR, textStatus) {
           alert("incorrecto: "+XHR.responseText);
        }
    });
    }