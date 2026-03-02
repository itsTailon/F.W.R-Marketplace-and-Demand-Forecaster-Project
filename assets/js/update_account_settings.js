// $.ajax({
//     url: "/backend/API/Auth/login.php",
//     type: 'POST',
//     data: {
//         email: email,
//         password: password
//     },
//     statusCode: {
//         200: () => { // If login was successful
//             location.href = "/dashboard.php"; // Redirect to dashboard
//         },
//         400: () => { // Bad request
//             $('.error-text').text("Bad Request");
//         },
//         401: () => { // Unauthorised (invalid credentials)
//             // Give error highlighting to both textboxes
//             $("#email-textbox").addClass("textbox--error");
//             $("#password-textbox").addClass("textbox--error");
//             $('.error-text').text("Incorrect Email or password");
//         },
//         500: () => { // Internal Server Error
//             $('.error-text').text("Internal Server Error");
//         }
//     }
// });

$('#update-email-button').click(function () {
    const email = $('#new-email').val();
    const userID = $('#userID').val();


    // Validate new email
    if (!validateEmail(email)) {
        $('#new-email-textbox').addClass("textbox--error");
        return;
    }

    $.ajax({
        url: "/backend/API/Model/account.php",
        type: 'PUT',
        data: {
            userID: userID,
            email: email
        },
        statusCode: {
            200: () => { // Success
                alert("E-mail address successfully updated.");
                location.reload();
            },
            400: () => { // Bad request
                alert("Error. Please try again later.");
            },
            403: () => { // Unauthorised (insufficient permissions)
                alert("Permission denied.");
            },
            500: () => { // Internal Server Error
                alert("Error. Please try again later.");
            }
        }
    });
});

$('#update-password-button').click(function () {
    const currentPassword = $('#current-password').val();
    const newPassword = $('#new-password').val();
    const newPasswordConfirmation = $('#confirm-new-password').val();
    const userID = $('#userID').val();

    var isInputValid = true;

    // Ensure that 'current password' value is not empty.
    if (currentPassword.trim() === "") {
        $('#current-password-textbox').addClass("textbox--error");
        isInputValid = false;
    }

    // Validate format of new password
    if (validatePassword(newPassword) !== 'PASS') {
        $('#new-password-textbox').addClass("textbox--error");
        isInputValid = false;
    }

    // Ensure that 'new password' value matches that of 'confirm new password'
    if (newPassword !== newPasswordConfirmation) {
        $('#confirm-new-password-textbox').addClass("textbox--error");
        isInputValid = false;
    }

    if (!isInputValid) {
        return;
    }

    $.ajax({
        url: "/backend/API/Auth/password.php",
        type: 'PUT',
        data: {
            userID: userID,
            newPassword: newPassword,
            currentPassword: currentPassword,
        },
        statusCode: {
            200: () => { // If Success
                alert("Password successfully updated.");
                // location.reload();
            },
            400: () => { // Bad request
                alert("Error. Please try again later.");
            },
            403: () => { // Unauthorised (insufficient permissions)
                alert("Permission denied.");
            },
            500: () => { // Internal Server Error
                alert("Error. Please try again later.");
            }
        }
    });
});