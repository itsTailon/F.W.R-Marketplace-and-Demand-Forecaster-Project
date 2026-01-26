/* getCharCount(str, char)
Description: Find the number of a given character in a string
Parameters:
- str: The string to search
- char: The character to count
Returns the number of char in str
*/
const getCharCount = (str, char) => {
    let count = 0;
    if (str === "") { return 0; } // If the string is empty just return 0
    for (const c of str) { // Iterate through each character in str
        if (c === char) { count++; } // If the character is equal to char, increment count
    }
    return count;
}

/* validateEmail(email)
Description: Determine if an email is in valid format
Parameters:
- email: The email to validate
Returns true or false depending on if the email is valid
*/
const validateEmail = (email) => {
    if (getCharCount(email, '@') != 1) { return false; } // If there is not exactly one @, email is invalid
    if (getCharCount(email, '.') == 0) { return false; } // If there is not at least one ., email is invalid
    let emailSplit = email.split('@'); // Split the email into two at the @
    if (emailSplit[0].length == 0 || emailSplit[1].length == 0) { return false; } // If either side is empty, email is invalid
    return true; // Otherwise, return true as the email has passed all checks
}

$("#login-btn").click(() => { // When login button is clicked
    // Get values from email and password fields
    var email = $("#email").val();
    var password = $("#password").val();

    if (!validateEmail(email)) { // If email is invalid
        // Highlight the email textbox with red using textbox--error class
        $("#email-textbox").addClass("textbox--error");
        $("#password-textbox").removeClass("textbox--error");
        $('.error-text').text("Invalid Email"); // Display relevant error message
        return false; // Return to halt further execution
    }

    if (password.length < 8) { // If password length is less than 8 (all passwords need to be at least 8 characters)
        $("#email-textbox").removeClass("textbox--error"); // Remove error highlight around email textbox if it was there
        $("#password-textbox").addClass("textbox--error"); // Highlight the password textbox
        $('.error-text').text("Password must be at least 8 characters");
        return false;
    }

    // If submitting, remove any prior errors
    $("#email-textbox").removeClass("textbox--error");
    $("#password-textbox").removeClass("textbox--error");
    $(".error-text").text("");

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
