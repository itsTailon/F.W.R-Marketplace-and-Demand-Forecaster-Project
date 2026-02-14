<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Account;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "reservations";

// Include page head



require_once 'partials/head.php';
if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('ERROR: Not logged in! <br> TODO: redirect to login page');
}

$acc = Authenticator::getCurrentUser();
if (!$acc) {
    header('Location: /login.php');
    die('');
}



// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';


if(Seller::existsWithID($acc->getUserID())) {
    require_once 'partials/active_reservations/Seller/active_reservations_seller.php';
}
else {
    require_once 'partials/active_reservations/Customer/active_reservations_customer.php';
}



?>





<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

