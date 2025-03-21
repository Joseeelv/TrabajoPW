const carousel = document.querySelector(".carousel-images");
const images = carousel.querySelectorAll("img");
const prevButton = document.querySelector(".carousel-button.prev");
const nextButton = document.querySelector(".carousel-button.next");
const indicators = document.querySelector(".carousel-indicators");

let currentIndex = 0;
let intervalId;

// Crear indicadores
images.forEach((_, index) => {
  const indicator = document.createElement("div");
  indicator.classList.add("indicator");
  indicator.addEventListener("click", () => goToSlide(index));
  indicators.appendChild(indicator);
});

function updateCarousel() {
  carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
  updateIndicators();
}

function updateIndicators() {
  document.querySelectorAll(".indicator").forEach((indicator, index) => {
    indicator.classList.toggle("active", index === currentIndex);
  });
}

function nextSlide() {
  currentIndex = (currentIndex + 1) % images.length;
  updateCarousel();
}

function prevSlide() {
  currentIndex = (currentIndex - 1 + images.length) % images.length;
  updateCarousel();
}

function goToSlide(index) {
  currentIndex = index;
  updateCarousel();
}

function startAutoPlay() {
  intervalId = setInterval(nextSlide, 3000);
}

function stopAutoPlay() {
  clearInterval(intervalId);
}

// Event Listeners
nextButton.addEventListener("click", () => {
  nextSlide();
  stopAutoPlay();
});

prevButton.addEventListener("click", () => {
  prevSlide();
  stopAutoPlay();
});

carousel.addEventListener("mouseenter", stopAutoPlay);
carousel.addEventListener("mouseleave", startAutoPlay);

// Inicializar
updateCarousel();
startAutoPlay();
