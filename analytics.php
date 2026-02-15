<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Analytics";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Ensure that user is a Seller (Seller-only page)
$acc = Authenticator::getCurrentUserSubclass();
if (!($acc instanceof Seller)) {
    header('Location: /dashboard.php');
    die('');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
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

