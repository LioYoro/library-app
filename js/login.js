document.addEventListener("DOMContentLoaded", function () {
  const wrapper = document.querySelector('.wrapper');
  const loginLink = document.querySelector('.login-link');
  const registerLink = document.querySelector('.register-link');
  const btnPopup = document.querySelector('.btnLogin-popup');
  const iconClose = document.querySelector('.icon-close');

  // Popup transitions
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

  if (btnPopup) {
    btnPopup.addEventListener('click', () => {
      wrapper.classList.add('active-popup');
      wrapper.classList.remove('active');
    });
  }

  if (iconClose) {
    iconClose.addEventListener('click', () => {
      wrapper.classList.remove('active-popup');
      wrapper.classList.remove('active');
    });
  }

  // Handle login AJAX
  const loginForm = document.querySelector("#login-form");
  const loginMessage = document.querySelector("#login-message");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(loginForm);
      const email = formData.get("email");
      const password = formData.get("password");

      fetch("login/process_login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
          email: email,
          password: password
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loginMessage.style.color = "green";
            loginMessage.textContent = "Login successful! Redirecting...";
            setTimeout(() => {
              window.location.href = "/library-app/test.php";
            }, 1000);
          } else if (data.redirect) {
            loginMessage.style.color = "orange";
            loginMessage.textContent = data.message;
            setTimeout(() => {
            window.location.href = "/library-app/test.php";
          }, 1000);
          } else {
            loginMessage.textContent = data.message || "Login failed.";
            loginMessage.style.color = "red";
          }
        })
        .catch(error => {
          console.error("Login error:", error);
          loginMessage.textContent = "Something went wrong.";
          loginMessage.style.color = "red";
        });
    });
  }
});