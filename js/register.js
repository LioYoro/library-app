document.addEventListener("DOMContentLoaded", function () {
    let currentStep = 1;

    // Move to next step
    function nextStep(step) {
        document.querySelector(`.step-${step}`).classList.add("hidden");
        document.querySelector(`.step-${step + 1}`).classList.remove("hidden");
        currentStep = step + 1;
    }

    // Move to previous step
    window.prevStep = function(step) {
        document.querySelector(`.step-${step + 1}`).classList.add("hidden");
        document.querySelector(`.step-${step}`).classList.remove("hidden");
        currentStep = step;
    }

    // Toggle education fields
    window.toggleEducationFields = function(level) {
        document.getElementById("shs-strand").classList.add("hidden");
        document.getElementById("college-course").classList.add("hidden");
        
        if (level === "SHS") {
            document.getElementById("shs-strand").classList.remove("hidden");
        } else if (level === "College" || level === "Graduate") {
            document.getElementById("college-course").classList.remove("hidden");
        }
    }

    // Toggle resident fields - Fixed to work with radio buttons
    window.toggleFields = function() {
        const residentRadios = document.querySelectorAll('input[name="resident"]');
        const barangaySection = document.getElementById("barangay_section");
        const citySection = document.getElementById("city_section");
        const barangaySelect = document.getElementById("barangay");
        const cityInput = document.getElementById("city");
        
        let selectedValue = null;
        residentRadios.forEach(radio => {
            if (radio.checked) {
                selectedValue = radio.value;
            }
        });

        // Reset both sections
        barangaySection.style.display = "none";
        citySection.style.display = "none";
        
        // Clear values when switching
        barangaySelect.value = "";
        cityInput.value = "";
        
        if (selectedValue === "yes") {
            barangaySection.style.display = "block";
            barangaySelect.required = true;
            cityInput.required = false;
        } else if (selectedValue === "no") {
            citySection.style.display = "block";
            cityInput.required = true;
            barangaySelect.required = false;
        } else {
            barangaySelect.required = false;
            cityInput.required = false;
        }
    }

    // Password validation functions
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");
    const passwordStrength = document.getElementById("passwordStrength");
    const matchMessage = document.getElementById("matchMessage");

    // Real-time password strength feedback
    passwordInput.addEventListener("input", function () {
        const value = passwordInput.value;
        
        if (value.length === 0) {
            passwordStrength.textContent = "";
            passwordInput.classList.remove("invalid-input", "valid-input");
            return;
        }
        
        if (value.length < 8) {
            passwordStrength.textContent = "Password must be at least 8 characters long";
            passwordStrength.style.color = "red";
            passwordInput.classList.add("invalid-input");
            passwordInput.classList.remove("valid-input");
        } else if (!/[A-Z]/.test(value)) {
            passwordStrength.textContent = "Password must contain at least one uppercase letter";
            passwordStrength.style.color = "red";
            passwordInput.classList.add("invalid-input");
            passwordInput.classList.remove("valid-input");
        } else if (!/\d/.test(value)) {
            passwordStrength.textContent = "Password must contain at least one number";
            passwordStrength.style.color = "red";
            passwordInput.classList.add("invalid-input");
            passwordInput.classList.remove("valid-input");
        } else {
            passwordStrength.textContent = "Strong password ✅";
            passwordStrength.style.color = "green";
            passwordInput.classList.remove("invalid-input");
            passwordInput.classList.add("valid-input");
        }
        
        checkPasswordMatch();
    });

    // Real-time password match feedback
    confirmInput.addEventListener("input", checkPasswordMatch);

    function checkPasswordMatch() {
        const pass = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm === "") {
            matchMessage.textContent = "";
            confirmInput.classList.remove("invalid-input", "valid-input");
            return;
        }
        
        if (pass === confirm) {
            matchMessage.textContent = "Passwords match ✅";
            matchMessage.style.color = "green";
            confirmInput.classList.remove("invalid-input");
            confirmInput.classList.add("valid-input");
        } else {
            matchMessage.textContent = "Passwords do not match ❌";
            matchMessage.style.color = "red";
            confirmInput.classList.add("invalid-input");
            confirmInput.classList.remove("valid-input");
        }
    }

    // Password visibility toggle
    document.querySelectorAll(".toggle-password").forEach(button => {
        button.addEventListener("click", function () {
            const targetId = this.getAttribute("data-target");
            const input = document.getElementById(targetId);
            
            if (input.type === "password") {
                input.type = "text";
                this.textContent = "🙈";
            } else {
                input.type = "password";
                this.textContent = "👁";
            }
        });
    });

    // Step 1 validation
    document.getElementById("step1Next").addEventListener("click", function () {
        const step1Inputs = document.querySelectorAll(".step-1 input[required], .step-1 select[required]");
        const errorDiv = document.getElementById("step1-error");
        errorDiv.textContent = "";

        // Check all required fields
        for (let input of step1Inputs) {
            if (!input.value.trim()) {
                errorDiv.textContent = `Please fill in the ${input.placeholder || input.name} field.`;
                input.focus();
                return;
            }
        }

        // Validate password strength
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

        if (!passwordRegex.test(password)) {
            errorDiv.textContent = "❌ Password must be at least 8 characters long, contain one uppercase letter, and one number.";
            document.getElementById("password").focus();
            return;
        }

        if (password !== confirmPassword) {
            errorDiv.textContent = "❌ Password and Confirm Password do not match.";
            document.getElementById("confirm_password").focus();
            return;
        }

        nextStep(1);
    });

    // Step 2 validation
    document.getElementById("step2Next").addEventListener("click", function () {
        const residentRadios = document.querySelectorAll('input[name="resident"]');
        let residentSelected = false;
        let selectedValue = null;

        residentRadios.forEach(radio => {
            if (radio.checked) {
                residentSelected = true;
                selectedValue = radio.value;
            }
        });

        if (!residentSelected) {
            alert("Please select whether you are from Mandaluyong or not.");
            return;
        }

        // Validate based on selection
        if (selectedValue === "yes") {
            const barangay = document.getElementById("barangay").value;
            if (!barangay) {
                alert("Please select your barangay.");
                document.getElementById("barangay").focus();
                return;
            }
        } else if (selectedValue === "no") {
            const city = document.getElementById("city").value.trim();
            if (!city) {
                alert("Please enter your city.");
                document.getElementById("city").focus();
                return;
            }
        }

        nextStep(2);
    });

    // Final form submission - FIXED THE MAJOR FIELD ISSUE
    document.getElementById("registerForm").addEventListener("submit", function (e) {
        e.preventDefault();

        // Final validation
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

        if (!passwordRegex.test(password)) {
            alert("Password must be at least 8 characters long, contain at least one uppercase letter, and one number.");
            return;
        }

        if (password !== confirmPassword) {
            alert("Password and Confirm Password do not match.");
            return;
        }

        // Prepare form data with proper field mapping
        const formData = new FormData();
        
        // Step 1 data
        formData.append('first_name', document.getElementById('first_name').value);
        formData.append('last_name', document.getElementById('last_name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('contact_number', document.getElementById('contact_number').value);
        formData.append('password', password);
        formData.append('gender', document.getElementById('gender').value);
        formData.append('age', document.getElementById('age').value);
        formData.append('religion', document.getElementById('religion').value);
        
        // Step 2 data - Handle resident selection properly
        const residentRadios = document.querySelectorAll('input[name="resident"]');
        let residentValue = '';
        residentRadios.forEach(radio => {
            if (radio.checked) {
                residentValue = radio.value;
            }
        });
        
        if (residentValue === 'yes') {
            formData.append('is_mandaluyong_resident', 'Yes');
            formData.append('barangay', document.getElementById('barangay').value);
            formData.append('city_outside_mandaluyong', '');
        } else if (residentValue === 'no') {
            formData.append('is_mandaluyong_resident', 'No');
            formData.append('barangay', '');
            formData.append('city_outside_mandaluyong', document.getElementById('city').value);
        }
        
        // Step 3 data - FIXED THE MAJOR FIELD ISSUE
        const educationLevel = document.querySelector('select[name="education_level"]').value;
        formData.append('education_level', educationLevel);
        
        if (educationLevel === 'SHS') {
            formData.append('strand', document.querySelector('select[name="strand"]').value || '');
            formData.append('major', '');
        } else if (educationLevel === 'College' || educationLevel === 'Graduate') {
            // FIXED: Use input[name="major"] instead of select[name="major"] since it's a datalist
            const majorInput = document.querySelector('input[name="major"]');
            formData.append('major', majorInput ? majorInput.value : '');
            formData.append('strand', '');
        } else {
            formData.append('strand', '');
            formData.append('major', '');
        }
        
        formData.append('school_name', document.querySelector('input[name="school_name"]').value);
        
        // Send OTP
        fetch('/library-app/login/send_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(response => {
            console.log('OTP Response:', response);
            if (response.includes('success') || response.trim() === 'success') {
                // FIXED: Properly hide forms and show OTP
                document.querySelector(".form-box.login").style.display = "none";
                document.getElementById("registerForm").style.display = "none";
                
                // Show OTP form and remove hidden class
                const otpBox = document.querySelector(".form-box.otp");
                otpBox.style.display = "block";
                otpBox.classList.remove("hidden");
            } else {
                alert("Error sending OTP: " + response);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert("Error: " + error);
        });
    });

    // OTP submission
    document.getElementById("otp-form").addEventListener("submit", function (e) {
        e.preventDefault();
        
        const otpData = new FormData();
        otpData.append('otp', document.querySelector('input[name="otp"]').value);
        
        fetch('/library-app/login/validate_otp.php', {
            method: 'POST',
            body: otpData
        })
        .then(res => res.text())
        .then(response => {
            console.log('OTP Validation Response:', response);
            if (response.includes('success') || response.trim() === 'success') {
                // OTP verified, now save user
                fetch('/library-app/login/save_user.php', {
                    method: 'POST'
                })
                .then(res => res.text())
                .then(saveResponse => {
                    console.log('Save User Response:', saveResponse);
                    if (saveResponse.includes('success') || saveResponse.trim() === 'success') {
                        document.getElementById("otp-message").innerHTML = `<span style="color:green;">Registration successful! You may now login.</span>`;
                        setTimeout(() => {
                            // Reset forms and show login - FIXED: Properly handle hidden classes
                            const otpBox = document.querySelector(".form-box.otp");
                            const loginBox = document.querySelector(".form-box.login");
                            
                            otpBox.style.display = "none";
                            otpBox.classList.add("hidden");
                            loginBox.style.display = "block";
                            loginBox.classList.remove("hidden");
                            
                            document.getElementById("registerForm").reset();
                            document.getElementById("otp-form").reset();
                            
                            // Reset to step 1
                            document.querySelector(".step-1").classList.remove("hidden");
                            document.querySelector(".step-2").classList.add("hidden");
                            document.querySelector(".step-3").classList.add("hidden");
                            currentStep = 1;
                            
                            // Clear password feedback
                            if (passwordStrength) passwordStrength.textContent = "";
                            if (matchMessage) matchMessage.textContent = "";
                            passwordInput.classList.remove("invalid-input", "valid-input");
                            confirmInput.classList.remove("invalid-input", "valid-input");
                        }, 2000);
                    } else {
                        document.getElementById("otp-message").innerHTML = `<span style="color:red;">${saveResponse}</span>`;
                    }
                })
                .catch(error => {
                    console.error('Save user error:', error);
                    document.getElementById("otp-message").innerHTML = `<span style="color:red;">Error saving user: ${error}</span>`;
                });
            } else {
                document.getElementById("otp-message").innerHTML = `<span style="color:red;">${response}</span>`;
            }
        })
        .catch(error => {
            console.error('OTP validation error:', error);
            document.getElementById("otp-message").innerHTML = `<span style="color:red;">Error: ${error}</span>`;
        });
    });

    // Toggle login/register display - FIXED: Properly handle hidden classes
    document.getElementById("show-register")?.addEventListener("click", function (e) {
        e.preventDefault();
        
        const loginBox = document.querySelector(".form-box.login");
        const registerForm = document.getElementById("registerForm");
        const otpBox = document.querySelector(".form-box.otp");
        
        loginBox.style.display = "none";
        registerForm.style.display = "block";
        otpBox.style.display = "none";
        otpBox.classList.add("hidden");
        
        document.querySelector(".step-1").classList.remove("hidden");
        document.querySelector(".step-2").classList.add("hidden");
        document.querySelector(".step-3").classList.add("hidden");
        currentStep = 1;
    });

    document.getElementById("show-login")?.addEventListener("click", function (e) {
        e.preventDefault();
        
        const loginBox = document.querySelector(".form-box.login");
        const registerForm = document.getElementById("registerForm");
        const otpBox = document.querySelector(".form-box.otp");
        
        loginBox.style.display = "block";
        registerForm.style.display = "none";
        otpBox.style.display = "none";
        otpBox.classList.add("hidden");
    });
});