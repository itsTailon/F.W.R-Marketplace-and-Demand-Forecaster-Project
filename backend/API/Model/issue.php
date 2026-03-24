<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\Issue;
use TTE\App\Model\IssueAlreadyResolvedException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchIssueException;
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

// POST request (create issue)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify user is a customer
    $accountType = $currentUser->getAccountType();

    if ($accountType != "customer") {
        // Non-customers cannot create issues, so give 403 error
        http_response_code(403);
        die("ERROR: Only consumers can report issues.");
    }

    // Make sure required fields are set
    if (
        !isset($_POST["issueTitle"]) || empty($_POST["issueTitle"]) ||
        !isset($_POST["issueText"]) || empty($_POST["issueText"]) ||
        !isset($_POST["reservationID"])
    ) {
        // If any required field is missing, this is a bad request (400)
        http_response_code(400);
        die("ERROR: Required fields missing!");
    }

    $reservationID = $_POST["reservationID"]; // Get ID of reservation being reported

    // Attempt to load the reservation
    try {
        $reservation = Reservation::load($reservationID);
    } catch (NoSuchReservationException $e) { // If the reservation does not exist
        // Give 404 not found error
        http_response_code(404);
        die("ERROR: Reservation does not exist!");
    }

    // Get user ID, title, and description of issue
    $customerID = $currentUser->getUserID();
    $title = $_POST["issueTitle"];
    $description = $_POST["issueText"];

    // Attempt to create the issue
    try {
        Issue::create(["customerID" => $customerID, "reservationID" => $reservationID, "description" => $description, "title" => $title]);
    } catch (MissingValuesException $e) { // Some value is missing
        // Return 400 bad request error
        http_response_code(400); 
        die("ERROR: Required fields missing!");
    }
} else if ($_SERVER["REQUEST_METHOD"] == "PUT") { // PUT request (respond to issue)
    // Get input
    $_PUT = array();
    parse_str(file_get_contents('php://input'), $_PUT);

    // Verify user is a seller
    $accountType = $currentUser->getAccountType();

    if ($accountType != "seller") {
        // Non-sellers cannot  respond to issues, so give 403 error
        http_response_code(403);
        die("ERROR: Only sellers can respond to issues.");
    }

    // Make sure required values are set
    if (
        !isset($_PUT["sellerResponse"]) || empty($_PUT["sellerResponse"]) ||
        !isset($_PUT["issueID"])
    ) {
        http_response_code(400); // Bad request
        die("ERROR: Required fields missing!");
    }

    $issueID = $_PUT["issueID"]; // Get issue ID

    // Attempt to load issue
    try {
        $issue = Issue::load($issueID);
    } catch (NoSuchIssueException $e) { // If issue does not exist
        // Return 403 so to not let the user know if the issue exists, for privacy reasons
        http_response_code(403);
        die("ERROR: Issue with that ID does not exist or does not belong to seller.");
    } catch (DatabaseException $e) { // Database error
        // Return 500 internal server error
        http_response_code(500);
        die();
    }

    // Attempt to get bundle of the reservation the issue is about
    try {
        $bundle = $issue->getBundle();
    } catch (DatabaseException $e) { // Bundle does not exist
        // Return 410 Gone
        http_response_code(410);
        die("ERROR: Bundle deleted.");
    } catch (NoSuchReservationException $e) { // Reservation does not exist
        http_response_code(410);
        die("ERROR: Reservation deleted.");
    }

    // Check if the seller owns the bundle
    $sellerID = $currentUser->getUserID();
    if ($bundle->getSellerID() !== $sellerID) {
        // If the seller does not own the bundle, they do not have permission to respond to an issue about it
        http_response_code(403);
        die("ERROR: Issue with that ID does not exist or does not belong to seller.");
    }

    // Get and set submitted response
    $sellerResponse = $_PUT["sellerResponse"];

    // Attempt to mark the issue as resolved with the seller response and current datetime
    try {
        $issue->markResolved(new DateTimeImmutable(), $sellerResponse);
    } catch (IssueAlreadyResolvedException $e) { // Issue already resolved
        http_response_code(409); // 409 conflict
        die("ERROR: Issue already resolved.");
    }

    // Attempt to update the issue
    try {
        $issue->update();
    } catch (MissingValuesException $e) { // Required values missing
        http_response_code(400);
        die("ERROR: Required values are missing.");
    } catch (DatabaseException $e) { // Database error
        http_response_code(500);
        die($e->getMessage());
    } 

    // Otherwise, return 200 (success)
    http_response_code(200);
    die("");
}

?>
