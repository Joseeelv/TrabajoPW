$(document).ready(function() {
  $('#updateProfileForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: '../functions/perfil.php',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function(response) {
        var result = JSON.parse(response);
        if (result.success) {
          alert(result.message);
          // Aquí puedes actualizar la imagen de perfil en la página si lo deseas
        } else {
          alert("Error: " + result.message);
        }
      },
      error: function() {
        alert("Ocurrió un error al procesar la solicitud.");
      }
    });
  });
});