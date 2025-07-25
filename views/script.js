document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.querySelector('.wrapper');
  const loginLink = document.querySelector('.login-link');
  const registerLink = document.querySelector('.register-link');
  const openLoginBtn = document.getElementById('openLoginModal');
  const closeBtn = document.getElementById('closeModal');

  if (registerLink) {
    registerLink.addEventListener('click', () => {
      wrapper.classList.add('active');
    });
  }

  if (loginLink) {
    loginLink.addEventListener('click', () => {
      wrapper.classList.remove('active');
    });
  }

  if (openLoginBtn) {
    openLoginBtn.addEventListener('click', () => {
      wrapper.classList.add('active-popup');
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      wrapper.classList.remove('active-popup');
    });
  }
});