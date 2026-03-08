<?php


use TTE\App\Model\InvalidRegestrationRequest;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchSellerRegistrationRequestException;
use TTE\App\Model\SellerReservationRequest;

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
            SellerReservationRequest::grant($_PUT["sellerRequestID"]);
        }
        else if($_PUT["action"] === "deny") {
            SellerReservationRequest::deny($_PUT["sellerRequestID"]);
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
    } catch (InvalidRegestrationRequest $e) {
        echo json_encode(http_response_code(400));
        die();
    }
} else {
    echo json_encode(http_response_code(405));
    die();
}
