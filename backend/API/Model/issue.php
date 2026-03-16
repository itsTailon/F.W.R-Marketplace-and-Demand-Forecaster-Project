<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\Issue;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchReservationException;
use TTE\App\Model\Reservation;

include '../../../vendor/autoload.php';

session_start();

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// If user isn't logged in, give 401 response
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

$currentUser = Authenticator::getCurrentUser();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accountType = $currentUser->getAccountType();

    switch ($accountType) {
        case "customer": {
            if (
                !isset($_POST["issueTitle"]) || empty($_POST["issueTitle"]) ||
                !isset($_POST["issueText"]) || empty($_POST["issueText"]) ||
                !isset($_POST["reservationID"])
            ) {
                http_response_code(400);
                die("ERROR: Required fields missing!");
            }

            $reservationID = $_POST["reservationID"];

            try {
                $reservation = Reservation::load($reservationID);
            } catch (NoSuchReservationException $e) {
                http_response_code(404);
                die("ERROR: Reservation does not exist!");
            }

            $customerID = $currentUser->getUserID();
            $description = $_POST["issueText"];
            $title = $_POST["issueTitle"];

            try {
                Issue::create(["customerID" => $customerID, "reservationID" => $reservationID, "description" => $description, "title" => $title]);
            } catch (MissingValuesException $e) {
                http_response_code(400); 
                die("ERROR: Required fields missing!");
            }

            break;
        }
    }

    http_response_code(200);
    die();
}
?>
