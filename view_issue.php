<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\Customer;
use TTE\App\Model\Issue;
use TTE\App\Model\Seller;

$DOCUMENT_TITLE = "Issue";

require_once('partials/head.php');

// Ensure that user is logged in
if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Get user (specialised) account object
$acc = Authenticator::getCurrentUserSubclass();


// Ensure that only customer and seller accounts can access this page.
if ($acc->getAccountType() !== "seller" && $acc->getAccountType() !== "customer") {
    header("Location: /dashboard.php");
    die('You cannot access this page.');
}

// Ensure that issue ID is valid and relates to an actual issue
if (!is_int(filter_var($_GET['id'], FILTER_VALIDATE_INT))) {
    header("Location: /404.php");
    die("Invalid issue ID.");
}
if (!Issue::existsWithID($_GET['id'])) {
    header("Location: /404.php");
    die("Invalid issue ID.");
}


// Load Issue object
$issue = Issue::load($_GET['id']);

// Ensure that issue ID belongs to the user
$reservation = $issue->getReservation();
if ($acc->getAccountType() == "customer") {
    if ($reservation->getPurchaserID() !== $acc->getUserID()) {
        header("Location: /404.php");
        die('You cannot access this page.');
    }
} else if ($acc->getAccountType() == "seller") {
    if (Bundle::load($reservation->getBundleID())->getSellerID() !== $acc->getUserID()) {
        header("Location: /404.php");
        die('You cannot access this page.');
    }
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

// Display appropriate view depending on account type
if ($acc->getAccountType() == "seller") {
    require_once('partials/view_issue/view_issue_seller.php');
} else if ($acc->getAccountType() == "customer") {
    require_once('partials/view_issue/view_issue_customer.php');
}

require_once('partials/footer.php');

?>