document.addEventListener('DOMContentLoaded', () => {
   // Manejo de cantidades de ingrediente con botones
   let botones = document.querySelectorAll('.ingr_btn');
   botones.forEach(boton => {
      boton.addEventListener('click', () => {
         let spanToMod = boton.textContent === '+' ? boton.previousElementSibling : boton.nextElementSibling;
         let cant_ingr = parseInt(spanToMod.textContent, 10);

         if (boton.textContent === '+') {
            if (cant_ingr < 2) cant_ingr++;
         } else {
            cant_ingr = Math.max(0, cant_ingr - 1);
         }

         spanToMod.textContent = cant_ingr;
      });
   });

   // Envío de cantidades de todos los ingredientes a la sesión
   let envio = document.getElementById("add_to_carrito");
   envio.addEventListener('click', (event) => {
      event.preventDefault(); // Evita el envío automático del formulario

      let lista_ingredientes = [];

      document.querySelectorAll(".ingredient-container").forEach(div => {
         let nombre = div.querySelector(".ingr-nombre").textContent.trim();
         let cantidad = parseInt(div.querySelector(".ingr-cant").textContent.trim(), 10);
         let id = div.querySelector(".ingr-id").textContent.trim();

         // Determinar si es extra, removido o normal
         let estado = cantidad > 1 ? "extra" : cantidad === 0 ? "removido" : "normal";

         lista_ingredientes.push([id, cantidad, estado]);
      });

      // Enviar el objeto como JSON en un campo oculto
      let inputHidden = document.getElementById("ingr_list_info");
      inputHidden.value = JSON.stringify(lista_ingredientes);
      console.log("Ingredientes enviados:", JSON.stringify(lista_ingredientes));

      // Enviar el formulario con los datos actualizados
      document.getElementById("form_add_carrito").submit();
   });
});
