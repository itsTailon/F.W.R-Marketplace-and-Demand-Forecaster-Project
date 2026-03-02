<?php

/*
 * Handles API 'seller' request
 */

// Import seller from Model directory
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\Seller;

include '../../../vendor/autoload.php';

session_start();

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

// TODO: Consider whether we should implement LOAD API

// if-elseif...-else statement block branching on the basis of request method
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
    //TODO: Add functionality to request handling method for PUT requests

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handling POST request method that calls create() method
    try {

        // Ensuring all required values are set
        if (empty($_POST["email"]) || empty($_POST["password"]) || empty($_POST["name"]) || empty($_POST["address"])) {

            // Throwing exception if field isn't present in retrieve data
            throw new MissingValuesException("Missing fields");
        }


        // Get array of fields for seller to create
        $fields = array(
            "email" => $_POST["email"],
            "password" => $_POST["password"],
            "name" => $_POST["name"],
            "address" => $_POST["address"],
        );

        // Checking that current user has permissions to create a Seller
        if (!RBACManager::isCurrentuserPermitted("seller_create")) {
            throw new NoSuchPermissionException("Account with ID " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to create seller account");
        }

        // Calling create() method, storing Seller object produced as $seller
        $seller = Seller::create($fields);

        // If successfully created a Seller, return that seller
        echo json_encode($seller);
        die();


    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die("NSP");
    } catch (DatabaseException $e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        echo json_encode(http_response_code(500));
        die("DBE");
    } catch (MissingValuesException $mv_e) {
        // Bad request not in the form required as input and produce JSON-encoded message
        echo json_encode(http_response_code(400));
        die("MVE");
    } catch (InvalidArgumentException $ia_e) {
        // Argument passed to method not of right form and return JSON-encoded message
        echo json_encode(http_response_code(400));
        die("IAE");
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    //TODO: Implement the delete method for seller API
}