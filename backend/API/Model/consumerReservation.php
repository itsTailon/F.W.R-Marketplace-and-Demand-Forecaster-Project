<?php

/*
 * Handles API 'consumerReservation' request
 */

use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Bundle;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBundleException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchReservationException;
use TTE\App\Model\ReservationStatus;
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Reservation;

include '../../../vendor/autoload.php';

session_start();

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}


if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    try {
        //
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // Check if input contains reservationID
        if (isset($_DELETE["reservationID"])) {
            $id = $_DELETE["reservationID"];
        } else {
            throw new MissingValuesException("Missing field");
        }

        // Check if the reservation exists
        if(Reservation::existsWithID($id)) {
            // load reservation details
            $reservationDetails = Reservation::load($id);

            // check if the consumer is allowed to cancel this reservation
            if(Authenticator::getCurrentUser()->getUserID() != $reservationDetails->getPurchaserID()){
                throw new NoSuchPermissionException("Consumer " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to cancel reservation " . $id);
            }

            // mark the reservation as canceled
            $reservationDetails->setStatus(ReservationStatus::Cancelled);

            // Update the database
            $reservationDetails->update();
            exit();
        } else {
            throw new NoSuchReservationException("No such reservation with ID " . $id);
        }
    } catch (NoSuchReservationException $e) {
        echo json_encode(http_response_code(404));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    } catch (MissingValuesException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST["bundleID"]) || !isset($_POST["purchaserID"])) {
            throw new MissingValuesException("Missing fields");
        }

        $fields = array(
            "bundleID" => 0,
            "purchaserID" => 0,
            "status" => ReservationStatus::Active
        );

        $bundleID = $_POST["bundleID"];
        $purchaserID = $_POST["purchaserID"];

        if (!Customer::existsWithID($purchaserID)) {
            throw new NoSuchCustomerException("No such customer with ID " . $purchaserID);
        }

        if (!Bundle::existsWithID($bundleID)) {
            throw new NoSuchBundleException("No such bundle with ID " . $bundleID);
        }

        if (ctype_digit($bundleID) && ctype_digit($purchaserID)) {
            $fields["bundleID"] = intval($bundleID);
            $fields["purchaserID"] = intval($purchaserID);
        } else {
            throw new InvalidArgumentException("Input contains invalid field type");
        }

        // Create the reservation
        $reservation = Reservation::Create($fields);

        // Return created reservation
        echo json_encode($reservation);
        exit();

    } catch (MissingValuesException) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchCustomerException) {
        echo json_encode(http_response_code(404));
        die();
    } catch (NoSuchBundleException) {
        echo json_encode(http_response_code(404));
        die();
    } catch (InvalidArgumentException) {
        echo json_encode(http_response_code(400));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        if (!isset($_GET["reservationID"])) {
            // Load the reservation id
            $id = $_GET['reservationID'];

            // Check if the id has a corresponding reservation
            if (!Reservation::existsWithID($id)) throw new NoSuchReservationException("No such reservation");

            // Load reservation with given id
            $reservation = Reservation::load($id);

            // get current user's ID and the reserved reservation
            $userID = Authenticator::getCurrentUser()->getUserID();

            // Check if customer has permission to get reservation
            if ($reservation->getPurchaserID() != $userID) throw new NoSuchPermissionException("Consumer " . $userID . " is not allowed to load reservation " . $id);

            // Load reservation
            $consumerReservation = Reservation::load($id);

            // Return reservation
            echo json_encode($consumerReservation);

            exit();
        } else if (isset($_GET["consumerID"])) {
            // Get seller ID
            $id = $_GET['consumerID'];

            // Check if ID has a consumer record
            if (!Customer::existsWithID($id)) throw new NoSuchCustomerException("No such customer with ID " . $id);

            // Load all of customer's reservations
            $reservations = Reservation::getAllReservationsForUser($id, "buyer");

            // Return loaded reservations
            echo json_encode($reservations);

            exit();
        } else {
            throw new MissingValuesException("Missing fields");
        }

    } catch (MissingValuesException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchReservationException $e) {
        echo json_encode(http_response_code(404));
        die();
    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    } catch (NoSuchCustomerException $e) {
        echo json_encode(http_response_code(404));
    }
} else {
    echo json_encode(http_response_code(405));
    die();
}
