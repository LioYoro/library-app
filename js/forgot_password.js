document.addEventListener("DOMContentLoaded", function () {
    // Show forgot password form
    document.getElementById("show-forgot-password")?.addEventListener("click", function (e) {
        e.preventDefault();
        showForm('forgot-password');
    });

    // Back to login buttons
    document.getElementById("back-to-login")?.addEventListener("click", function (e) {
        e.preventDefault();
        showForm('login');
    });

    document.getElementById("back-to-login-2")?.addEventListener("click", function (e) {
        e.preventDefault();
        showForm('login');
    });

    // Function to show specific form
    function showForm(formType) {
        // Hide all forms
        document.querySelector(".form-box.login").style.display = "none";
        document.getElementById("registerForm").style.display = "none";
        document.querySelector(".form-box.otp").style.display = "none";
        document.querySelector(".form-box.forgot-password").style.display = "none";
        document.querySelector(".form-box.reset-password").style.display = "none";

        // Show requested form
        if (formType === 'login') {
            document.querySelector(".form-box.login").style.display = "block";
        } else if (formType === 'register') {
            document.getElementById("registerForm").style.display = "block";
        } else if (formType === 'otp') {
            document.querySelector(".form-box.otp").style.display = "block";
        } else if (formType === 'forgot-password') {
            document.querySelector(".form-box.forgot-password").style.display = "block";
        } else if (formType === 'reset-password') {
            document.querySelector(".form-box.reset-password").style.display = "block";
        }
    }

    // Forgot password form submission
    document.getElementById("forgot-password-form")?.addEventListener("submit", function (e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById("forgot-message");
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = "Sending...";
        submitBtn.disabled = true;

        fetch('/library-app/login/forgot_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log('Forgot Password Response:', data);
            
            if (data.includes('success') || data.includes('verify_reset_code.php')) {
                messageDiv.innerHTML = '<span style="color: green;">Reset code sent to your email!</span>';
                setTimeout(() => {
                    showForm('reset-password');
                    messageDiv.innerHTML = '';
                }, 2000);
            } else if (data.includes('Email not found')) {
                messageDiv.innerHTML = '<span style="color: red;">Email not found in our records.</span>';
            } else {
                messageDiv.innerHTML = '<span style="color: red;">Error sending reset code. Please try again.</span>';
            }
        })
        .catch(error => {
            console.error('Forgot Password Error:', error);
            messageDiv.innerHTML = '<span style="color: red;">Something went wrong. Please try again.</span>';
        })
        .finally(() => {
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Reset password form submission
    document.getElementById("reset-password-form")?.addEventListener("submit", function (e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById("reset-message");
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');
        
        // Client-side validation
        if (newPassword !== confirmPassword) {
            messageDiv.innerHTML = '<span style="color: red;">Passwords do not match.</span>';
            return;
        }

        if (newPassword.length < 8) {
            messageDiv.innerHTML = '<span style="color: red;">Password must be at least 8 characters long.</span>';
            return;
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = "Resetting...";
        submitBtn.disabled = true;

        fetch('/library-app/login/verify_reset_code.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log('Reset Password Response:', data);
            
            if (data.includes('Password reset successful') || data.includes('success')) {
                messageDiv.innerHTML = '<span style="color: green;">Password reset successful! You can now login.</span>';
                setTimeout(() => {
                    showForm('login');
                    messageDiv.innerHTML = '';
                    // Clear the reset form
                    document.getElementById("reset-password-form").reset();
                }, 2000);
            } else if (data.includes('Invalid code')) {
                messageDiv.innerHTML = '<span style="color: red;">Invalid reset code. Please check your email.</span>';
            } else if (data.includes('Passwords do not match')) {
                messageDiv.innerHTML = '<span style="color: red;">Passwords do not match.</span>';
            } else {
                messageDiv.innerHTML = '<span style="color: red;">Error resetting password. Please try again.</span>';
            }
        })
        .catch(error => {
            console.error('Reset Password Error:', error);
            messageDiv.innerHTML = '<span style="color: red;">Something went wrong. Please try again.</span>';
        })
        .finally(() => {
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
});