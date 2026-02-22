<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Customer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../../customer/customer_loginpage/customer_loginpage.css">
</head>
<body>

<div class="main-wrapper">

    <header>

        <img src="../../photos/brand_elements/label_name.png"
             alt="Scruffs & Chyrrs"
             class="header-label">

        <div class="nav-pill">
            <img src="../../photos/brand_elements/sea_bunny.png" class="nav-logo">

            <nav>
                <a href="#">Home</a>
                <a href="#">Products</a>
                <a href="#">About Us</a>
                <a href="#">Contacts</a>
            </nav>

            <div class="user-icon">👤</div>
        </div>
    </header>

    <main class="login-section">
        <h1 class="login-title">Login</h1>

        <form id="customerForm" class="login-form">
            <div class="input-group">
                <label>Email</label>
                <input type="email" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" minlength="8" required>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="login-button">Login</button>

            <p class="register-text">
                Don't have an account?
                <a href="#" class="register-link">Register here</a>
            </p>
        </form>
    </main>

    <footer>
        <div class="footer-wave"></div>
        <div class="footer-bar">
            <p>© 2025, Scruffs&Chyrrs</p>
        </div>
    </footer>

</div>

<script>
document.getElementById('customerForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        alert("Please fill out all fields correctly.");
    }
});
</script>

</body>
</html>
