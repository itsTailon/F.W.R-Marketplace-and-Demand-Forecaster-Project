<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Dashboard";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <?php

            // Get current user object
            $user = Authenticator::getCurrentUserSubclass();

            // Render appropriate dashboard template depending on user (account) type
            if ($user instanceof Seller) {
                // Seller dashboard (home)
                require 'partials/dashboard/seller/dashboard_seller_home.php';

            } else if ($user instanceof \TTE\App\Model\Customer) {
                // Customer dashboard (home)
                require 'partials/dashboard/customer/dashboard_customer_home.php';
            }
        ?>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

