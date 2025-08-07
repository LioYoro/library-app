<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/library-app/css/logintest.css">
</head>
<body>
<div class="wrapper active-popup">
    <span class="icon-close">
        <ion-icon name="close"></ion-icon>
    </span>
        
    <!-- Login Form -->
    <div class="form-box login">
        <h2>Login</h2>
        <form id="login-form" method="POST">
            <div class="input-box">
                <span class="icon"><ion-icon name="mail"></ion-icon></span>
                <input type="email" name="email" required>
                <label>Email</label>
            </div>
            <div class="input-box">
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                <input type="password" name="password" required>
                <label>Password</label>
            </div>
            <div class="remember-forgot">
                <label><input type="checkbox" name="remember"> Remember Me</label>
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" class="btn">Login</button>
            <div id="login-message" class="form-message"></div>
            <div class="login-register">
                <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
            </div>
        </form>
    </div>
        
    <!-- Register Form -->
    <form id="registerForm">
        <p class="signup">Already have an account? <a href="#" id="show-login">Login</a></p>
                
        <div class="form-step step-1">
            <div id="form-error-message" style="color: red; margin-bottom: 10px;"></div>
            <h2>Step 1: Primary Information</h2>
            <input type="text" name="first_name" id="first_name" placeholder="First Name" required />
            <input type="text" name="last_name" id="last_name" placeholder="Last Name" required />
            <input type="email" name="email" id="email" placeholder="Email" required />
            <input type="text" name="contact_number" id="contact_number" placeholder="Contact Number" required />
                        
            <div style="position: relative;">
                <input type="password" name="password" id="password" placeholder="Password" required />
                <button type="button" class="toggle-password" data-target="password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">👁</button>
            </div>
            <div id="passwordStrength" style="font-size: 12px; margin-top: 5px;"></div>
                        
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required />
                <button type="button" class="toggle-password" data-target="confirm_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">👁</button>
            </div>
            <div id="matchMessage" style="font-size: 12px; margin-top: 5px;"></div>
                        
            <select name="gender" id="gender" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <input type="number" name="age" id="age" placeholder="Age" required />
            <select name="religion" id="religion" required>
                <option value="">Pumili ng Relihiyon</option>
                <option value="Catholic">Katoliko</option>
                <option value="Christian">Kristiyano</option>
                <option value="Iglesia ni Cristo">Iglesia ni Cristo (INC)</option>
                <option value="Islam">Islam</option>
                <option value="Protestant">Protestant</option>
                <option value="Others">Wala sa nabanggit</option>
            </select>
            <div id="step1-error" style="color: red; margin-top: 10px;"></div>
            <button type="button" id="step1Next">Next</button>
        </div>
                
        <div class="form-step step-2 hidden">
            <h2>Step 2: Secondary Information</h2>
            <p><strong>Taga-Mandaluyong ka ba?</strong></p>
            <label><input type="radio" name="resident" value="yes" onchange="toggleFields()"> Oo</label>
            <label><input type="radio" name="resident" value="no" onchange="toggleFields()"> Hindi</label>
                        
            <div id="barangay_section" style="display:none;">
                <label for="barangay">Kung oo, anong barangay?</label>
                <select name="barangay" id="barangay">
                    <option value="">--Pumili--</option>
                    <option value="Addition Hills">Addition Hills</option>
                    <option value="Bagong Silang">Bagong Silang</option>
                    <option value="Barangka Drive">Barangka Drive</option>
                    <option value="Barangka Ibaba">Barangka Ibaba</option>
                    <option value="Barangka Ilaya">Barangka Ilaya</option>
                    <option value="Barangka Itaas">Barangka Itaas</option>
                    <option value="Buayang Bato">Buayang Bato</option>
                    <option value="Burol">Burol</option>
                    <option value="Daang Bakal">Daang Bakal</option>
                    <option value="Hagdang Bato Itaas">Hagdang Bato Itaas</option>
                    <option value="Hagdang Bato Libis">Hagdang Bato Libis</option>
                    <option value="Harapin ang Bukas">Harapin ang Bukas</option>
                    <option value="Highway Hills">Highway Hills</option>
                    <option value="Hulo">Hulo</option>
                    <option value="Mabini-J. Rizal">Mabini-J. Rizal</option>
                    <option value="Malamig">Malamig</option>
                    <option value="Mauway">Mauway</option>
                    <option value="Namayan">Namayan</option>
                    <option value="New Zaniga">New Zaniga</option>
                    <option value="Old Zaniga">Old Zaniga</option>
                    <option value="Pag-asa">Pag-asa</option>
                    <option value="Plainview">Plainview</option>
                    <option value="Pleasant Hills">Pleasant Hills</option>
                    <option value="Poblacion">Poblacion</option>
                    <option value="San Jose">San Jose</option>
                    <option value="Vergara">Vergara</option>
                    <option value="Wack-Wack-Greenhills East">Wack-Wack-Greenhills East</option>
                </select>
            </div>
                        
            <div id="city_section" style="display:none;">
                <label for="city">Kung hindi, anong lungsod?</label>
                <input type="text" name="city_outside_mandaluyong" id="city" placeholder="City Outside Mandaluyong">
            </div>
                        
            <button type="button" id="step2Next">Next</button>
            <button type="button" onclick="prevStep(1)">Back</button>
        </div>
                
        <div class="form-step step-3 hidden">
            <h2>Step 3: Education</h2>
            <label>Education Level:</label>
            <select name="education_level" onchange="toggleEducationFields(this.value)" required>
                <option value="">Select</option>
                <option value="SHS">Senior High School</option>
                <option value="College">College</option>
                <option value="Graduate">Graduate</option>
            </select>
                        
            <div id="shs-strand" class="conditional hidden">
                <label>Strand:</label>
                <select name="strand">
                    <option value="">Select Strand</option>
                    <option value="STEM">STEM</option>
                    <option value="ABM">ABM</option>
                    <option value="HUMSS">HUMSS</option>
                    <option value="GAS">GAS</option>
                    <option value="TVL">TVL</option>
                    <option value="Sports">Sports</option>
                    <option value="Arts and Design">Arts and Design</option>
                </select>
            </div>
                        
            <div id="college-course" class="conditional hidden">
                <label>Course:</label>
                <select name="major">
                    <option value="">Select Course</option>
                    <option value="BS Computer Science">BS Computer Science</option>
                    <option value="BS Information Technology">BS Information Technology</option>
                    <option value="BS Business Administration">BS Business Administration</option>
                    <option value="BS Accountancy">BS Accountancy</option>
                    <option value="BS Education">BS Education</option>
                    <option value="BA Communication">BA Communication</option>
                </select>
            </div>
                        
            <input type="text" name="school_name" placeholder="School Name" required />
            <button type="submit">Submit</button>
            <button type="button" onclick="prevStep(2)">Back</button>
        </div>
    </form>
        
    <!-- OTP Verification -->
    <div class="form-box otp" style="display: none;">
        <h2>Email Verification</h2>
        <form id="otp-form" method="POST">
            <div class="input-box">
                <span class="icon"><ion-icon name="keypad"></ion-icon></span>
                <input type="text" name="otp" maxlength="6" required>
                <label>Enter OTP</label>
            </div>
            <button type="submit" class="btn">Verify OTP</button>
            <div id="otp-message" class="form-message"></div>
        </form>
    </div>
</div>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<!-- JavaScript -->
<script src="/library-app/js/login.js"></script>
<script src="/library-app/js/register.js"></script>

<script>
// Override the login.js behavior since we're in an iframe
document.addEventListener("DOMContentLoaded", function () {
    console.log('logintest.php loaded in iframe');
    
    // The wrapper should already have active-popup class, but let's make sure the login form is visible
    const wrapper = document.querySelector('.wrapper');
    const loginForm = document.querySelector('.form-box.login');
    
    if (wrapper && loginForm) {
        // Make sure the login form is visible
        wrapper.classList.add('active-popup');
        wrapper.classList.remove('active'); // Remove active class to show login, not register
        console.log('Login form should now be visible');
    }
    
    // Handle the close button to send message to parent
    const closeBtn = document.querySelector('.icon-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            // Send message to parent window to close modal
            if (window.parent !== window) {
                window.parent.postMessage('closeModal', '*');
            }
        });
    }
    
    // Override the login form submission to send success message to parent
    const loginFormElement = document.querySelector("#login-form");
    if (loginFormElement) {
        loginFormElement.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(loginFormElement);
            const email = formData.get("email");
            const password = formData.get("password");
            
            fetch("/library-app/login/process_login.php", {
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
                const loginMessage = document.querySelector("#login-message");
                if (data.success) {
                    loginMessage.style.color = "green";
                    loginMessage.textContent = "Login successful! Redirecting...";
                    setTimeout(() => {
                        // Send success message to parent window
                        if (window.parent !== window) {
                            window.parent.postMessage('loginSuccess', '*');
                        } else {
                            window.location.href = "/library-app/test.php";
                        }
                    }, 1000);
                } else if (data.redirect) {
                    loginMessage.style.color = "orange";
                    loginMessage.textContent = data.message;
                    setTimeout(() => {
                        if (window.parent !== window) {
                            window.parent.postMessage('loginSuccess', '*');
                        } else {
                            window.location.href = "/library-app/test.php";
                        }
                    }, 1000);
                } else {
                    loginMessage.textContent = data.message || "Login failed.";
                    loginMessage.style.color = "red";
                }
            })
            .catch(error => {
                console.error("Login error:", error);
                const loginMessage = document.querySelector("#login-message");
                loginMessage.textContent = "Something went wrong.";
                loginMessage.style.color = "red";
            });
        });
    }
});
</script>

</body>
</html>
