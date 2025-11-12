/* scripts/main.js */


document.addEventListener('DOMContentLoaded', () => {
// Fade-in de secciones
const sections = document.querySelectorAll('section');
const options = { threshold: 0.2 };


const observer = new IntersectionObserver((entries) => {
entries.forEach(entry => {
if (entry.isIntersecting) {
entry.target.classList.add('visible');
}
});
}, options);


sections.forEach(section => {
section.classList.add('fade-in');
observer.observe(section);
});


// Scroll suave para enlaces internos
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
anchor.addEventListener('click', function (e) {
e.preventDefault();
const target = document.querySelector(this.getAttribute('href'));
if (target) {
window.scrollTo({
top: target.offsetTop - 50,
behavior: 'smooth'
});
}
});
});
});


/* Animaciones CSS */
const style = document.createElement('style');
style.innerHTML = `
.fade-in {
opacity: 0;
transform: translateY(30px);
transition: all 0.8s ease-out;
}


.fade-in.visible {
opacity: 1;
transform: translateY(0);
}`;
document.head.appendChild(style);