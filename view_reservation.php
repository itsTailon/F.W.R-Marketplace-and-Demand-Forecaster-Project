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

// TODO: Replace this with graceful redirect to login page
// (Temporary code) Halt rendering if user not logged in
if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('ERROR: Not logged in! <br> TODO: redirect to login page');
}

$reservationID = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (empty($reservationID)) {
    die("Couldn't find Reservation with that id lil bro.");
}

$reservation = Reservation::load($reservationID);
if (!$reservation || $reservation->getStatus() != ReservationStatus::Active) {
    die("reservation not found");
}



// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

$acc = Authenticator::getCurrentUser();


if($acc) {
    if(Seller::existsWithID($acc->getUserID())){
        require_once 'partials/view_reservation/seller/view_reservation_seller.php';
    }    
    else {
        require_once 'partials/view_reservation/customer/view_reservation_customer.php';
    }

}
else {
    header("Location: /login.php");
    die();
}


?>





<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

