<?php

use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

/*
 * Handles API 'register' request
 */

// TODO: Provide API documentation

include '../../../vendor/autoload.php';

session_start();

// Make sure username, email, and password are not empty
if (
        !isset($_POST['username']) || empty($_POST['username']) ||
        !isset($_POST['email'])    || empty($_POST['email'])    ||
        !isset($_POST['password']) || empty($_POST['password'])
    )
{
    // Return invalid request 400
    http_response_code(response_code: 400);
    die();
}

// Validate username (must be at least 3 characters)
if (strlen($_POST['username']) < 3) {
    http_response_code(response_code: 400);
    die();
}

// Validate email address
if (!filter_var(value: $_POST['email'], filter: FILTER_VALIDATE_EMAIL)) {
    http_response_code(response_code: 400);
    die();
}

// Validate password
// TODO: Implement frontend password check in PHP
if (strlen(string: $_POST['password']) < 8) {
    http_response_code(response_code: 400);
    die();
}

$PDO = DatabaseHandler::getPDO();

// Check if email already in use by another account
$stmt = $PDO->prepare(query: "SELECT userID FROM account WHERE email = :email");
$stmt->execute(params: ["email" => $_POST['email']]);
$row = $stmt->fetch(); // Attempt to fetch the first row returned by that statement, if it exists

if ($row) { // Another account was found with that email
    http_response_code(response_code: 409); // Return 409 (conflict) error
    die();
}

// Check if username already in use by another customer
$stmt = $PDO->prepare(query: "SELECT customerID FROM customer WHERE username = :username");
$stmt->execute(params: ["username" => $_POST['username']]);
$row = $stmt->fetch();

if ($row) { // Another customer account was found with that username
    http_response_code(response_code: 409); // Return 409 again
    die();
}

try { 
    TTE\App\Model\Customer::create(fields: ["email" => $_POST['email'], "username" => $_POST['username'], "password" => $_POST['password']]);
} catch (DatabaseException $e) { // Some other SQL-related error occurred
    http_response_code(response_code: 500); // Return a 500 Internal Server Error
    die();
}

http_response_code(response_code: 200); // If no issues arose, return 200 for success
die();
