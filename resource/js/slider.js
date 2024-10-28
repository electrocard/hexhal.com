const slider = document.querySelector('.slider');
const slides = document.querySelectorAll('.slide');
const dotsContainer = document.querySelector('.dots');
let currentIndex = 0;

// Créer les points de navigation
slides.forEach((_, index) => {
  const dot = document.createElement('div');
  dot.classList.add('dot');
  dot.addEventListener('click', () => goToSlide(index));
  dotsContainer.appendChild(dot);
});

const dots = document.querySelectorAll('.dot');

function goToSlide(index) {
  currentIndex = index;
  updateSlider();
}

function updateSlider() {
  slider.style.transform = `translateX(${-currentIndex * 100}vw)`;
  slides.forEach((slide, index) => {
    slide.classList.toggle('active', index === currentIndex);
  });
  dots.forEach((dot, index) => {
    dot.classList.toggle('active', index === currentIndex);
  });
}

function nextSlide() {
  currentIndex = (currentIndex + 1) % slides.length;
  updateSlider();
}

function previousSlide() {
  currentIndex = (currentIndex - 1 + slides.length) % slides.length;
  updateSlider();
}

// Gestion des événements clavier
document.addEventListener('keydown', function(event) {
  if (event.key === 'ArrowRight') {
    nextSlide();
  } else if (event.key === 'ArrowLeft') {
    previousSlide();
  }
});

// Initialisation
updateSlider();

// Navigation automatique (optionnel)
let autoSlideInterval = setInterval(nextSlide, 9000);

// Fonction pour réinitialiser le timer de défilement automatique
function resetAutoSlideTimer() {
  clearInterval(autoSlideInterval);
  autoSlideInterval = setInterval(nextSlide, 5000);
}

// Ajouter la réinitialisation du timer lors des interactions
document.addEventListener('keydown', resetAutoSlideTimer);
dotsContainer.addEventListener('click', resetAutoSlideTimer);
