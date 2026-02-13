<?php
/*
 * Handles API 'sellerReservation' request
 */

use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Bundle;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try{
        // Check if necessary values are set
        if(!isset($_POST['reservationStatus']) || !isset($_POST['reservationID'])){
            throw new MissingValuesException("Missing fields");
        }

        // Get id of reservation and the status to update it with
        $id = $_POST['ReservationID'];
        $status = $_POST['reservationStatus'];

        // Check if the given status is valid
        switch ($status){
            case "Completed":
                $rStatus = ReservationStatus::Completed;
                break;
            case "NoShow":
                $rStatus = ReservationStatus::NoShow;
                break;
            default:
                throw new InvalidArgumentException("Invalid status");
        }

        // Check if reservation with given exists
        if(!Reservation::existsWithID($id)){
            throw new NoSuchReservationException("No such reservation");
        }

        // Load reservation
        $reservation = Reservation::load($id);

        // Set reservation status to new status
        $reservation->setStatus($rStatus);

        // Update database with new values
        $reservation->update();
        exit();

    } catch (MissingValuesException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (InvalidArgumentException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchReservationException $e) {
        echo json_encode(http_response_code(404));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    try {
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // Check if a field has no value
        if (isset($_DELETE['reservationID'])) {
            // Load the reservation id
            $id = $_DELETE['reservationID'];
        } else {
            throw new MissingValuesException("Missing field");
        }

        // Check if a reservation with given ID exists
        if (!Reservation::existsWithID($id)) throw new NoSuchReservationException("No such reservation");

        // Load reservation with given id
        $reservation = Reservation::load($id);

        // get current user's ID and the reserved bundle
        $userID = Authenticator::getCurrentUser()->getUserID();
        $bundle = Bundle::load($reservation->getBundleID());

        // Check if seller has permission to cancel bundle
        if($bundle->getSellerID() != $userID) throw new NoSuchPermissionException("Seller " . $userID . " is not allowed to cancel reservation " . $id);

        // Mark reservation canceled
        $reservation->setStatus(ReservationStatus::Cancelled);
        $reservation->update();

        exit();

    } catch (MissingValuesException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchReservationException $e) {
        echo json_encode(http_response_code(404));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Check that there is a valid ID
        if(!isset($_GET['reservationID'])) throw new MissingValuesException("Missing fields");

        // Load the reservation id
        $id = $_GET['reservationID'];

        // Check if the id has a corresponding reservation
        if(!Reservation::existsWithID($id)) throw new NoSuchReservationException("No such reservation");

        // Load reservation with given id
        $reservation = Reservation::load($id);

        // get current user's ID and the reserved bundle
        $userID = Authenticator::getCurrentUser()->getUserID();
        $bundle = Bundle::load($reservation->getBundleID());

        // Check if seller has permission to cancel bundle
        if($bundle->getSellerID() != $userID) throw new NoSuchPermissionException("Seller " . $userID . " is not allowed to load reservation " . $id);

        // Load reservation
        $sellerReservation = Reservation::load($id);

        // Return reservation
        echo json_encode($sellerReservation);

        exit();

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
    }
} else {
    echo json_encode(http_response_code(405));
    die();
}