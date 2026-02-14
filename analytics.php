<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Analytics";

// Include page head
require_once 'partials/head.php';

// TODO: Replace this with graceful redirect to login page
// (Temporary code) Halt rendering if user not logged in
if (!Authenticator::isLoggedIn()) {
    die('ERROR: Not logged in! <br> TODO: redirect to login page');
}

$acc = Authenticator::getCurrentUser();


if(!$acc) {
    die('ERROR: Not logged in!');
}
else {
    $acc_id = $acc->getUserID();
    if(!Seller::existsWithID($acc_id)) {
        header('Location: /dashboard.php');
        die("Only sellers can access this page.");
    }
}
// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';


?>

<div class="analytics-wrapper">
    <div class="analytics">
        <?php
        require 'partials/analytics/analytics.php';
        ?>
    </div>
</div>




<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

