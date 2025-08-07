document.addEventListener("DOMContentLoaded", () => {
  const asideBoxes = document.querySelectorAll('.aside-box');

  asideBoxes.forEach(box => {
    box.addEventListener('mouseenter', () => {
      box.style.boxShadow = '0 0 0 2px #3B82F6';
    });
    box.addEventListener('mouseleave', () => {
      box.style.boxShadow = 'none';
    });
  });

  // Optional: Highlight active page button
  const pageButtons = document.querySelectorAll('.page-btn');
  pageButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      pageButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
});
