$("#submit-btn").click(() => { // When submit button is clicked
    // Get values of fields
    var businessName = $("#business-name").val();
    var businessAddress = $("#business-address").val();
    var email = $("#email").val();
    var password = $("#password").val();
    var confirm_password = $("#confirm-password").val();

    // Remove error highlighting from any textbox that may have it
    $("#business-name-textbox").removeClass("textbox--error");
    $("#business-address-textbox").removeClass("textbox--error");
    $("#email-textbox").removeClass("textbox--error");
    $("#password-textbox").removeClass("textbox--error");
    $("#confirm-password-textbox").removeClass("textbox--error");
    $('.error-text').text(""); // Clear red error text

    // Business name must be at least 3 characters
    if (businessName.length < 3) {
        $("#business-name-textbox").addClass("textbox--error"); // Add error highlighting to business name textbox
        $('.error-text').text("Business name must be at least 3 characters");
        return false;
    }

    // Business must have an address
    if (businessAddress.length == 0) {
        $("#business-address-textbox").addClass("textbox--error"); // Add error highlighting to business address textbox
        $('.error-text').text("A business address is required");
        return false;
    }

    // Email must be a valid email
    if (!validateEmail(email)) {
        $("#email-textbox").addClass("textbox-error"); // Add error highlighting to email textbox
        $('.error-text').text("Invalid Email");
        return false;
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
        url: "/backend/API/Auth/seller_register.php",
        type: 'POST',
        data: {
            businessName: businessName,
            businessAddress: businessAddress,
            email: email,
            password: password
        },
        statusCode: {
            200: () => { // If registration was successful
                alert("Application submitted");
            },
            400: () => { // Bad request
                $('.error-text').text("Invalid input");
            },
            409: () => { // Conflict
                $('.error-text').text("Email already taken");
            },
            500: () => { // Internal Server Error
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});

$("#login-btn").click(() => { // If login button is clicked
    location.href = "/login.php"; // Redirect to login form
});

$("#customer-register-btn").click(() => { // If consumer signup button is clicked
    location.href = "/register.php"; // Redirect to consumer signup form
});
