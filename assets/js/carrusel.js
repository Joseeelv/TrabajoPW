document.addEventListener("DOMContentLoaded", () => {
    let currentIndex = 0;
    const totalSlides = document.querySelectorAll('.slide').length;
    const carousel = document.querySelector('.carousel');

    function updateCarousel() {
        carousel.style.transform = `translateX(-${currentIndex * 100}vw)`;
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }

    document.querySelector(".btn-right").addEventListener("click", nextSlide);
    document.querySelector(".btn-left").addEventListener("click", prevSlide);

    setInterval(nextSlide, 8000); 
});
