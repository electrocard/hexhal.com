document.addEventListener('DOMContentLoaded', function() {
  const images = document.querySelectorAll('.hover-effect');

  images.forEach(img => {
    img.addEventListener('mouseover', function() {
      // Aucune action supplémentaire pour le glow
    });

    img.addEventListener('mouseout', function() {
      // Aucune action supplémentaire pour le glow
    });
    
    // Déclencher manuellement l'événement 'load' si l'image est déjà chargée
    if (img.complete) {
      img.dispatchEvent(new Event('load'));
    }
  });
});
