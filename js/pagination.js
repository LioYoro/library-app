// Save scroll position before reload
document.querySelectorAll('.aside-pagination a').forEach(link => {
    link.addEventListener('click', () => {
        sessionStorage.setItem('scrollPos', window.scrollY);
    });
});

// Restore scroll position after reload with smooth scroll
window.addEventListener('load', () => {
    const savedPos = sessionStorage.getItem('scrollPos');
    if (savedPos) {
        window.scrollTo({
            top: parseInt(savedPos, 10),
            behavior: 'smooth'
        });
        sessionStorage.removeItem('scrollPos');
    }
});
