<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\SellerRegistrationRequest;

$DOCUMENT_TITLE = "View Request";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

$acc = Authenticator::getCurrentUser();

// TODO: uncomment when ready
// $acc = Authenticator::getCurrentUserSubclass();
// if (!($acc instanceof Maintainer)) {
//     header('Location: /dashboard.php');
//     die('You must be a maintainer to view this page.');
// }

// No ID passed, so redirect to 404
if (!isset($_GET['id'])) {
    header('Location: /404.php');
    die();
}

// Check that int was passed as ID
$requestID = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!is_int($requestID)) {
    header('Location: /404.php');
    die();
}

// Ensure that ID corresponds to a request
if (!SellerRegistrationRequest::existsWithID($requestID)) {
    header('Location: /404.php');
    die();
}

// Load request
$request = SellerRegistrationRequest::load($requestID);

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';
?>


<div class="registration-requests-wrapper">
    <nav class="registration-requests-nav">
        <a class="button button--rounded" href="/dashboard.php">
            <span>Requests</span>
        </a>
        <a class="button button--rounded" href="/dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
            <span>Home</span>
        </a>
    </nav>

    <div class="request-view">
        <h1 class="request-view-title">Seller Name: <?php echo $request->getSellerName(); ?></h1>
        <span class="request-view-email">Email: <?php echo $request->getSellerEmail(); ?></span>
        <span class="request-view-address">Address: <?php echo $request->getSellerAddress(); ?></span>
        <span class="request-view-evidence">Provided evidence:<br><i><?php echo $request->getDetails(); ?></i></span>
        
        <nav class="registration-requests-request-nav">
            <button id="approve-btn" class="button button--rounded button--green registration-requests-request-nav-button-clickable" data-request-id="<?php echo $requestID; ?>">Approve</button>
            <button id="deny-btn" class="button button--rounded red registration-requests-request-nav-button-clickable" data-request-id="<?php echo $requestID; ?>">Deny</button>
        </nav>
    </div>
</div>

<script src="assets/js/account_approve.js"></script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

