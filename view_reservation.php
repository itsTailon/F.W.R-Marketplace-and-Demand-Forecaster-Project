<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "View Reservations";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

$acc = Authenticator::getCurrentUser();
$accType = $acc->getAccountType();

// No ID passed, so redirect to 404
if (!isset($_GET['id'])) {
    header('Location: /404.php');
    die();
}

// Check that int was passed as ID
$reservationID = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!is_int($reservationID)) {
    header('Location: /404.php');
    die();
}

// Ensure that ID corresponds to a reservation
if (!Reservation::existsWithID($reservationID)) {
    header('Location: /404.php');
    die();
}

// Load reservation
$reservation = Reservation::load($reservationID);

// Ensure that reservation is actually active.
if ($reservation->getStatus() != ReservationStatus::Active) {
    header('Location: /404.php');
    die();
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

$acc = Authenticator::getCurrentUserSubclass();

if ($acc instanceof Seller) {
    require_once 'partials/view_reservation/seller/view_reservation_seller.php';
    require_once 'partials/dashboard/seller/dashboard_sidebar.php';
} else if ($acc instanceof Customer) {
    require_once 'partials/view_reservation/customer/view_reservation_customer.php';
    require_once 'partials/dashboard/customer/dashboard_sidebar.php';
}

?>





<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

