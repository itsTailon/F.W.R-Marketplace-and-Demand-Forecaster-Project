<?php
/*
 * Handles API 'login' request
 */

// TODO: Provide API documentation

include '../../../vendor/autoload.php';

session_start();

// Ensure validity of request (i.e. required fields are set and not empty)
if (
    !isset($_POST['password']) || empty($_POST['password'])  ||
    !isset($_POST['email'])    || empty($_POST['email'])      )
{
    // Invalid request (status 400)
    http_response_code(400); // TODO: Factor http_response_code and die calls out into a global 'send response' function w/ JSON encoding for messages
    die();
}

// Validate e-mail address (format)
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    // Invalid e-mail address, thus invalid request (status 400)
    http_response_code(400); // TODO: Factor http_response_code and die calls out into a global 'send response' function w/ JSON encoding for messages
    die();
}

// Attempt authentication
if (\TTE\App\Auth\Authenticator::authenticateUser($_POST['email'], $_POST['password'])) {
    // Successfully authenticated and logged-in (status 200)
    http_response_code(200); // TODO: Factor http_response_code and die calls out into a global 'send response' function w/ JSON encoding for messages
    die();

} else {
    // Authentication failure (status 401)
    http_response_code(401); // TODO: Factor http_response_code and die calls out into a global 'send response' function w/ JSON encoding for messages
    die();
}

// No plausible case was triggered, so assume server error (status 500)
http_response_code(500);
die();
