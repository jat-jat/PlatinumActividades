/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function() {
    obtenerDetallesAlumno();
});
function obtenerDetallesAlumno(){
   var id = sessionStorage.getItem("MatriculaAlumno");   
   $.ajax({
        url: "php/alumnos_detalles.php",
        data:{            
            id:id,
            fn:"informacion"
        },
        type: "POST",
        datatype: "text",        
        success: function(resultado){
            $('#informacion_alumno').empty();
            $('#informacion_alumno').append(resultado);
            llenarTabla(id);
        },
        error: function(xhr, textStatus){
           alert(xhr.responseText);           
        }
    });
}
function llenarTabla(id){
    $.ajax({
        url: "php/alumnos_detalles.php",
        data:{            
            id:id,
            fn:"contenido-tabla"
        },
        type: "POST",
        datatype: "text",        
        success: function(resultado){
            $('#contenidoTabla').empty();
            $('#contenidoTabla').append(resultado);            
        },
        error: function(xhr, textStatus) {
           alert(xhr.responseText);
        }
    });
}

