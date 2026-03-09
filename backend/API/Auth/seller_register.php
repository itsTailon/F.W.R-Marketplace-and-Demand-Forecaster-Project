<?php

use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\SellerRegistrationRequest;

/*
 * Handles API 'seller_register' request
 */

// TODO: Provide API documentation

include '../../../vendor/autoload.php';

session_start();

if (
    !isset($_POST['businessName']) || empty($_POST['businessName']) ||
    !isset($_POST['businessAddress']) || empty($_POST['businessAddress']) ||
    !isset($_POST['email']) || empty($_POST['email']) ||
    !isset($_POST['password']) || empty($_POST['password']) ||
    !isset($_POST['details']) || empty($_POST['details'])
) {
    // Bad request 400
    http_response_code(response_code: 400);
    die();
}

$businessName = $_POST['businessName'];
$businessAddress = $_POST['businessAddress'];
$email = $_POST['email'];
$password = $_POST['password'];
$details = $_POST['details'];

// Business name must be 3+ characters
if (strlen($businessName) < 3) {
    http_response_code(response_code: 400);
    die();
}

// Email address must be a valid email
if (!filter_var(value: $email, filter: FILTER_VALIDATE_EMAIL)) {
    http_response_code(response_code: 400);
    die();
}

// Details need to be at least 50 characters
if (strlen($details) < 50) {
    http_response_code(response_code: 400);
    die();
}

// Validate password
// TODO: Implement frontend password check in PHP
if (strlen(string: $password) < 8) {
    http_response_code(response_code: 400);
    die();
}

// Make sure there is no seller already registered with the name AND address
if (SellerRegistrationRequest::existsWithNameAndAddress($businessName, $businessAddress)) {
    http_response_code(response_code: 409); // Return 409 conflict status
    die();
}

try {
    SellerRegistrationRequest::create([
        "sellerName" => $businessName,
        "sellerAddress" => $businessAddress,
        "sellerEmail" => $email,
        "password" => $password,
        "details" => $details
    ]);
    http_response_code(response_code: 200);
    die();
} catch (ValueError $e) {
    print_r("Value Error");
    http_response_code(response_code: 400);
    die();
} catch (DatabaseException $e) {
    http_response_code(response_code: 500);
    die();
}
