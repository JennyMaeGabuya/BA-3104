document.addEventListener('DOMContentLoaded', function () {
    console.log('Login page loaded successfully!');

    const loginForm = document.getElementById('loginForm');

    // Handle login form submission
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;

        // Basic validation
        if (!email || !password) {
            alert('Please fill in all fields');
            return;
        }

        // Email validation
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return;
        }

        // Send data to PHP login controller
        try {
            const formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);

            const response = await fetch("../../controllers/login_controller.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            console.log(result);

            alert(result.msg);

            // Redirect based on PHP controller output
            if (result.success && result.redirect) {
                window.location.href = result.redirect;
            }

        } catch (error) {
            console.error("Login error:", error);
            alert("An error occurred. Please try again.");
        }
    });

    // Email validation helper function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Input focus effects
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });
});


