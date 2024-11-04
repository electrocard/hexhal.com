document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.button');
    const overlay = document.getElementById('overlay');
    const mainContent = document.getElementById('main-content');

    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            
            document.body.classList.add('button-hovered');
            targetContent.style.display = 'block';
        });

        button.addEventListener('mouseleave', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            
            document.body.classList.remove('button-hovered');
            targetContent.style.display = 'none';
        });
    });
});
