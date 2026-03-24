<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\SellerRegistrationRequest;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\Maintainer;

$DOCUMENT_TITLE = "Seller Registration Requests";

require_once 'partials/head.php';
if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// TODO: uncomment when ready
// $acc = Authenticator::getCurrentUserSubclass();
// if (!($acc instanceof Maintainer)) {
//     header('Location: /dashboard.php');
//     die('You must be a maintainer to view this page.');
// }

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

$stmt = DatabaseHandler::getPDO()->prepare(
    "SELECT id
    FROM seller_registration_request
    WHERE status = 'pending'
");

// Attempt to execute the statement
try {
    $stmt->execute();
    $requests = $stmt->fetchAll(\PDO::FETCH_COLUMN);
} catch (\PDOException $e){
    echo "<script>alert('Error fetching requests.');</script>
        <span>
            Error fetching requests. Please try again.
        </span>";
    $requests = array();
}
?>


<div class="registration-requests-wrapper">
    <nav class="registration-requests-nav">
        <a class="button button--rounded" href="/dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
            <span>Home</span>
        </a>
    </nav>

    <h1 class="registration-requests-title">Seller Account Requests</h1> 

    <?php if (!$requests): ?>
        <span class="registration-requests-text-none">No Seller Account Requests</span>
    
    <?php else: ?>
        <div class="registration-requests-list">
            <?php foreach ($requests as $requestID): ?>
                <?php
                    $name = SellerRegistrationRequest::Load($requestID)->getSellerName();
                ?>
                <div class="registration-requests-request">
                    <p class="registration-requests-request-description">Seller Name: <?php echo $name; ?></p>
                    <nav class="registration-requests-request-nav">
                        <button id="approve-btn" class="button button--rounded button--green registration-requests-request-nav-button-clickable" data-request-id="<?php echo $requestID; ?>">Approve</button>
                        <button id="deny-btn" class="button button--rounded red registration-requests-request-nav-button-clickable" data-request-id="<?php echo $requestID; ?>">Deny</button>
                        <a class="button button--rounded registration-requests-request-nav-view" href="/view_request.php?id=<?php echo $requestID; ?>">View</a>
                    </nav>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="assets/js/account_approve.js"></script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

