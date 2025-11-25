// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('Signup page loaded successfully!');

    const signupForm = document.getElementById('signupForm');

    // Handle signup form submission
    signupForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const name = document.getElementById('signupName').value.trim();
        const email = document.getElementById('signupEmail').value.trim();
        const phone = document.getElementById('signupPhone').value.trim();
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('signupConfirmPassword').value;
        const agreeTerms = document.getElementById('agreeTerms') ? document.getElementById('agreeTerms').checked : true;

        // Basic validation
        if (!name || !email || !phone || !password || !confirmPassword) {
            alert('Please fill in all fields');
            return;
        }

        // Email validation
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return;
        }

        // Password validation
        if (password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }

        // Password match validation
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        // Terms agreement validation
        if (!agreeTerms) {
            alert('Please agree to the Terms & Conditions');
            return;
        }

        // SEND DATA TO BACKEND
        try {
            const formData = new FormData();
            formData.append("name", name);
            formData.append("email", email);
            formData.append("phone", phone);
            formData.append("password", password);
            formData.append("confirm", confirmPassword);

            const response = await fetch("../../controllers/signup_controllers.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            console.log("Server response:", result);

            alert(result.msg);

            if (result.success) {
                // Redirect to login page
                window.location.href = "../auth/login.php";
            }

        } catch (error) {
            console.error("Error during signup:", error);
            alert("Something went wrong. Please try again.");
        }
    });

    // Email validation helper function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Add input focus effects
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="date"], select');
    inputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });
});
