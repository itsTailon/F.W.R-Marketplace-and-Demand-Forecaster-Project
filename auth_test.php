<?php
include 'vendor/autoload.php';

//session_start();

//$pdo = \TTE\App\Model\DatabaseHandler::getPDO();
//
//echo 'Attempt auth (invalid email)'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::authenticateUser('jane.doe@example.com', 'password'));
//echo '<br>';
//
//echo 'Attempt auth (valid email, invalid pwd)'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::authenticateUser('john.doe@example.com', 'pwd'));
//echo '<br>';
//
//echo 'Attempt auth (valid email, valid pwd)'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::authenticateUser('john.doe@example.com', 'password'));
//echo '<br>';
//
//echo 'Test Authenticator::isLoggedIn() when logged in'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::isLoggedIn());
//echo '<br>';
//
//echo 'Test Authenticator::getCurrentUser() when logged in'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::getCurrentUser());

//echo '<br>';
//
//echo 'Test Authenticator::logout() when logged in'; echo '<br>';
//\TTE\App\Auth\Authenticator::logout();
//echo '<br>';
//
//echo 'Test Authenticator::isLoggedIn() when logged out'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::isLoggedIn());
//echo '<br>';
//
//echo 'Test Authenticator::getCurrentUser() when logged out'; echo '<br>';
//var_dump(\TTE\App\Auth\Authenticator::getCurrentUser());
//echo '<br>';

//\TTE\App\Auth\Authenticator::logout();

//\TTE\App\Auth\Authenticator::logout();
//var_dump($_SESSION);


?>

<?php
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Auth Testing";

// Include page head
require_once 'partials/head.php';
?>

<button id="valid-customer-login">Test customer login</button>
<button id="valid-seller-login">Test seller login</button>


<button id="invalid-login">Test Invalid login</button>

<button id="logout">Test Logout</button>



<script>
    $("#valid-customer-login").click(function () {
        $.ajax({
            method: 'POST',
            url: 'backend/API/Auth/login.php',
            data: {
                email: 'john.doe@example.com',
                password: 'password',
            },
            statusCode: {
                400: function () {
                    alert('Status 400');
                },

                401: function () {
                    alert('Status 401');
                },

                200: function () {
                    alert('Status 200');
                },
            }
        });
    });

    $("#valid-seller-login").click(function () {
        $.ajax({
            method: 'POST',
            url: 'backend/API/Auth/login.php',
            data: {
                email: 'shop@example.com',
                password: 'password',
            },
            statusCode: {
                400: function () {
                    alert('Status 400');
                },

                401: function () {
                    alert('Status 401');
                },

                200: function () {
                    alert('Status 200');
                },
            }
        });
    });

    $("#invalid-login").click(function () {
        $.ajax({
            method: 'POST',
            url: 'backend/API/Auth/login.php',
            data: {
                email: 'john@example.com',
                password: 'password',
            },
            statusCode: {
                400: function () {
                    alert('Status 400');
                },

                401: function () {
                    alert('Status 401');
                },

                200: function () {
                    alert('Status 200');
                },
            }
        });
    });

    $("#logout").click(function () {
        $.ajax({
            method: 'POST',
            url: 'backend/API/Auth/logout.php',
            statusCode: {
                200: function () {
                    alert('Status 200');
                },
            }
        });
    });
</script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

