const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const btnPopup = document.querySelector('.btnLogin-popup');
const iconClose = document.querySelector('.icon-close');

const loginForm = wrapper.querySelector('.login');
const registerForm = wrapper.querySelector('.register');

registerLink.addEventListener('click', (e) => {
    e.preventDefault();
    wrapper.classList.add('active');           // your current toggle
    loginForm.style.display = 'none';          // hide login form
    registerForm.style.display = 'block';      // show register form
});

loginLink.addEventListener('click', (e) => {
    e.preventDefault();
    wrapper.classList.remove('active');        // your current toggle
    registerForm.style.display = 'none';       // hide register form
    loginForm.style.display = 'block';          // show login form
});

btnPopup.addEventListener('click', () => {
    wrapper.classList.add('active-popup');
    // Show login form by default when popup opens
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
});

iconClose.addEventListener('click', () => {
    wrapper.classList.remove('active-popup');
});
