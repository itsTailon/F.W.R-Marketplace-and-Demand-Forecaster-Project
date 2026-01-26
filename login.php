<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/assets/css/style.css">
        <title>Sign In</title>
    </head>
    <body>
        <div class="login-form">
            <div class="circle blue middle"></div>
            <h1 class="login-form__title">Login</h1>
            <p class="error-text"></p>
            <div class="textbox textbox--size-fill" data-type="email" data-label="Email" data-id="email" id="email-textbox"></div>
            <div class="textbox textbox--size-fill" data-type="password" data-label="Password" data-id="password" id="password-textbox"></div>
            <button class="button button--text-align-left button-submit" id="login-btn">> Log In</button>
            <button class="button button--text-align-left button-submit white" id="register-btn">> Sign Up</button>
        </div>

        <script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
        <script src="/assets/js/components/text-inputs.js"></script>
        <script src="/assets/js/components/validation.js"></script>
        <script src="/assets/js/login.js"></script>
    </body>
</html>
