<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchSellerRegistrationRequestException;
use TTE\App\Model\SellerRegistrationRequest;

include '../../../vendor/autoload.php';

session_start();

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Getting fields from input and storing under $_PUT to use as you would a superglobal
        $_PUT = array();
        parse_str(file_get_contents('php://input'), $_PUT);

        // check all values are here
        if (!isset($_PUT["sellerRequestID"]) || !isset($_PUT["action"])) {
            throw new MissingValuesException("Missing fields");
        }

        // TODO: Check if maintainer account

        if($_PUT["action"] === "grant") {
            $sellerRequest = SellerRegistrationRequest::load($_PUT["sellerRequestID"]);
            $sellerRequest->grant();
        }
        else if($_PUT["action"] === "deny") {
            $sellerRequest = SellerRegistrationRequest::load($_PUT["sellerRequestID"]);
            $sellerRequest->deny();
        }
        else{
            throw new InvalidArgumentException("Unknown action");
        }

        exit();

    } catch(MissingValuesException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (InvalidArgumentException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchSellerRegistrationRequestException $e) {
        echo json_encode(http_response_code(404));
        die();
    }
} else {
    echo json_encode(http_response_code(405));
    die();
}