document.addEventListener("DOMContentLoaded", () => {
    let currentIndex = 0;
    const slides = document.querySelectorAll('.slide img');
    const totalSlides = slides.length;
    const carousel = document.querySelector('.carousel');
    const carouselContainer = document.querySelector('.carousel-container');
    const btnLeft = document.querySelector(".btn-left");
    const btnRight = document.querySelector(".btn-right");

    function updateCarousel() {
        carousel.style.transform = `translateX(-${currentIndex * 100}vw)`;
        updateBackground(currentIndex);
        updateRegisterButton();
    }

    function updateBackground(index) {
        
        setTimeout(() => {
            carouselContainer.style.backgroundImage = `url(${slides[index].src})`;
        }, 500); // Espera un poco para cambiar la imagen (la mitad del tiempo del fade-out)
    }
    function firstupdateBackground(index) {
            carouselContainer.style.backgroundImage = `url(${slides[index].src})`;
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }

    function updateRegisterButton() {
        const btnRegister = document.querySelector('.btn-register');
        if (currentIndex === 1 || currentIndex === 2) {
            btnRegister.classList.add('hidden'); // Ocultar con fade
        } else {
            btnRegister.classList.remove('hidden'); // Mostrar con fade
        }
    }
    btnRight.addEventListener("click", nextSlide);
    btnLeft.addEventListener("click", prevSlide);
    firstupdateBackground(currentIndex); // Inicializa el fondo con la primera imagen

    updateBackground(currentIndex); 

    setInterval(nextSlide, 10000); // Cambia cada 8 segundos
});
