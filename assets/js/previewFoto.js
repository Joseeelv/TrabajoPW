document.getElementById("foto").addEventListener("change", function(event) {
    const file = event.target.files[0]; // Obtener el archivo seleccionado
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById("previewImage");
            preview.src = e.target.result; // Asignar la imagen cargada al <img>
            preview.style.display = "block"; // Mostrar la imagen
        };
        reader.readAsDataURL(file); // Leer el archivo como una URL base64
    }
});
