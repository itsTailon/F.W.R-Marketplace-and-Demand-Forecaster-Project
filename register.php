<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/assets/css/style.css">
        <title>Sign Up</title>
    </head>
    <body>
        <div class="login-form">
            <div class="circle blue middle"></div>
            <h1 class="login-form__title">Sign Up</h1>
            <p class="error-text"></p>
            <div class="textbox textbox--size-fill" data-type="username" data-label="Username" data-id="username" id="username-textbox"></div>
            <div class="textbox textbox--size-fill" data-type="email" data-label="Email" data-id="email" id="email-textbox"></div>
            <div class="textbox textbox--size-fill" data-type="password" data-label="Password" data-id="password" id="password-textbox"></div>
            <div class="textbox textbox--size-fill" data-type="password" data-label="Confirm Password" data-id="confirm-password" id="confirm-password-textbox"></div>
            <button class="button button--text-align-left button-submit" id="register-btn">> Sign Up</button>
            <button class="button button--text-align-left button-submit white" id="login-btn">> Log In</button>
        </div>

        <script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
        <script src="/assets/js/components/text-inputs.js"></script>
        <script src="/assets/js/components/validation.js"></script>
        <script src="/assets/js/register.js"></script>
    </body>
</html>
