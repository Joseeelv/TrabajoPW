document.addEventListener('DOMContentLoaded', () => {
   /** Manejo de cantidades de ingrediente con botones */
   botones = document.querySelectorAll('.ingr_btn');
   botones.forEach(boton => {
      boton.addEventListener('click', () => {
         if (boton.textContent == '+') {
            spanToMod = boton.previousElementSibling;
            cant_ingr = parseInt(spanToMod.textContent, 10);
            if (cant_ingr < 2) {
               cant_ingr++;
            }
         } else{
            spanToMod = boton.nextElementSibling;
            cant_ingr = parseInt(spanToMod.textContent, 10);
            cant_ingr = cant_ingr - 1;
            cant_ingr = Math.max(0, cant_ingr);
         }
         spanToMod.textContent = cant_ingr;
      })
      
   });

   /** Envio de cantidades de todos los ingredientes a la sesion */
   envio = document.getElementById("add_to_carrito");
   envio.addEventListener('click', () => {
      let lista_ingredientes = {};

      document.querySelectorAll(".ingredient-container").forEach(div => {
         let nombre = div.querySelector(".ingr-nom").textContent.trim();
         let cantidad = div.querySelector(".ingr-cant").textContent.trim();
         lista_ingredientes[nombre] = cantidad;
      });

      // Enviar el objeto como JSON en un campo oculto
      document.getElementById("ingr_list_info").value = JSON.stringify(lista_ingredientes);
      console.log(JSON.stringify(lista_ingredientes))
      document.getElementById("form_add_carrito").submit();
   })
})