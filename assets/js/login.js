$("#login-btn").click(() => { // When login button is clicked
    // Get values from email and password fields
    var email = $("#email").val();
    var password = $("#password").val();

    // Remove all error highlighting of text boxes and error text
    $("#email-textbox").removeClass("textbox--error");
    $("#password-textbox").removeClass("textbox--error");
    $('.error-text').text("");

    if (!validateEmail(email)) { // If email is invalid
        // Highlight the email textbox with red using textbox--error class
        $("#email-textbox").addClass("textbox--error");
        $('.error-text').text("Invalid Email"); // Display relevant error message
        return false; // Return to halt further execution
    }

    if (password.length < 8) { // If password length is less than 8 (all passwords need to be at least 8 characters)
        $("#password-textbox").addClass("textbox--error"); // Highlight the password textbox
        $('.error-text').text("Password must be at least 8 characters");
        return false;
    }

    // Submit POST request
    $.ajax({
        url: "/backend/API/Auth/login.php",
        type: 'POST',
        data: {
            email: email,
            password: password
        },
        statusCode: {
            200: () => { // If login was successful
                location.href = "/dashboard.php"; // Redirect to dashboard
            },
            400: () => { // Bad request
                $('.error-text').text("Bad Request");
            },
            401: () => { // Unauthorised (invalid credentials)
                // Give error highlighting to both textboxes
                $("#email-textbox").addClass("textbox--error");
                $("#password-textbox").addClass("textbox--error");
                $('.error-text').text("Incorrect Email or password");
            },
            500: () => { // Internal Server Error
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});

$("#register-btn").click(() => { // When register button is clicked
    location.href = "/register.php"; // Go to registration page
});
