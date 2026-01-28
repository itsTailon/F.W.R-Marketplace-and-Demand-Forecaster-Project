$("#register-btn").click(() => { // When sign up button is clicked
    // Get values from username, email, password, and confirmation fields
    var username = $("#username").val();
    var email = $("#email").val();
    var password = $("#password").val();
    var confirm_password = $("#confirm-password").val();

    // Remove all error highlighting of text boxes and error text
    $("#username-textbox").removeClass("textbox--error");
    $("#email-textbox").removeClass("textbox--error");
    $("#password-textbox").removeClass("textbox--error");
    $("#confirm-password-textbox").removeClass("textbox--error");
    $('.error-text').text("");

    if (username.length < 3) {
        $("#username-textbox").addClass("textbox--error"); // Add error highlighting to username textbox
        $('.error-text').text("Username must be at least 3 characters");
        return false;
    }

     if (!validateEmail(email)) { // If email is invalid
        // Highlight the email textbox with red using textbox--error class
        $("#email-textbox").addClass("textbox--error");
        $('.error-text').text("Invalid Email"); // Display relevant error message
        return false; // Return to halt further execution
    }

    let passwordValidationResult = validatePassword(password); // Run password validation check

    if (passwordValidationResult !== "PASS") { // If the password did not pass the test
        $("#password-textbox").addClass("textbox--error"); // Add error highlighting to password textbox
        $('.error-text').text(passwordValidationResult); // Display why the password failed
        return false;
    }

    if (password !== confirm_password) { // If the confirm password content is not the same as the password
        $("#confirm-password-textbox").addClass("textbox--error");
        $('.error-text').text("Passwords do not match");
        return false;
    }

    // Submit POST request
    $.ajax({
        url: "/backend/API/Auth/register.php",
        type: 'POST',
        data: {
            username: username,
            email: email,
            password: password
        },
        statusCode: {
            200: () => { // If registration was successful
                location.href = "/login.php" // Go to login page
            },
            500: () => { // Internal Server Error
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});

$("#login-btn").click(() => { // When login button is clicked
    location.href = "/login.php"; // Go to login page
});
