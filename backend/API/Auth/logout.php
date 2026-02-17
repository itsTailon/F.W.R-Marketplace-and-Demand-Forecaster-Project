<?php
/*
 * Handles API 'logout' request
 */

// TODO: Provide API documentation

include '../../../vendor/autoload.php';

session_start();

// Log user out
\TTE\App\Auth\Authenticator::logout();

// Send success response

header('Location: /dashboard.php');
http_response_code(200); // TODO: Factor http_response_code and die calls out into a global 'send response' function w/ JSON encoding for messages
die();