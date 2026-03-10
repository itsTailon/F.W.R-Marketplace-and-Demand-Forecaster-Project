<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchReservationException;
use TTE\App\Model\Reservation;
use TTE\App\Model\Bundle;
use TTE\App\Model\Seller;

require 'partials/head.php';

// If user is not logged in, briefly display an error
// and then redirect to login
if (!Authenticator::isLoggedIn()) {
    echo <<<XYZ
    <p>ERROR: Not logged in!</p>
    <p>If not redirected automatically, please click <a href="login.php">here</a>.</p>
    <script>
        function redirectToLogin() {
            location.href = "/login.php";
        }
    </script>
    XYZ;
    die();
}

$currentUser = $_SESSION['currentUser'];

function showReservationUnavailable() {
    echo <<<XYZ
    <p>ERROR: Reservation not found or does not belong to customer.</p>
    <script>
        function redirectToDashboard() {
            location.href = "/dashboard.php"
        }

        setTimeout(redirectToDashboard, 3000);
    </script>
    XYZ;
    die();
}

function showReservationNoBundle() {
    echo <<<XYZ
    <p>ERROR: Reservation does not correspond to a valid bundle.</p>
    <script>
        function redirectToDashboard() {
            location.href = "/dashboard.php"
        }

        setTimeout(redirectToDashboard, 3000);
    </script>
    XYZ;
    die();
}

$reservationID = $_GET['id']; // Get ID parameter

// If no ID was given
if (!isset($reservationID) || empty($reservationID)) {
    showReservationUnavailable(); // Show error page
}

// Try to get the reservation and the corresponding bundle
try {
    $reservation = Reservation::load($reservationID); // Load reservation with that ID
    // If reservation does not belong to customer
    if ($reservation->getPurchaserID() != $currentUser->getUserID()) {
        showReservationUnavailable(); // Show error page
    }

    // Attempt to load the bundle corresponding to reservation
    try {
        $bundle = Bundle::load($reservation->getBundleID());
    } catch (DatabaseException $e) { // Bundle not found
        showReservationNoBundle(); // Show no bundle error
    }
} catch (DatabaseException $e) { // Database error
    showReservationUnavailable();
} catch (NoSuchReservationException $e) { // Reservation does not exist
    showReservationUnavailable();
}

$bundleTitle = $bundle->getTitle(); // Get title of reserved bundle

// Try to get bundle seller name; if seller account does not exist, assume the account was deleted
try {
    $bundleSellerID = $bundle->getSellerID();
    $bundleSeller = Seller::load($bundleSellerID);
    $bundleSellerName = $bundleSeller->getName();
} catch (DatabaseException $e) {
    $bundleSellerName = "Deleted Account";
}

$rrp_gbx = $bundle->getRrpGBX(); // Get RRP in pence

$rrp_pounds = intdiv($rrp_gbx, 100); // Get the pounds of RRP by integer dividing by 100
$rrp_pence = $rrp_gbx % 100; // Get the remaining pence by the modulo operation

// Convert both numbers to strings
$rrp_pounds_str = strval($rrp_pounds); 
$rrp_pence_str = strval($rrp_pence);

// If there is only 1 digits in pence (we want pence to be two digits)
if (strlen($rrp_pence_str) == 1) {
    $rrp_pence_str = '0' . $rrp_pence_str; // Add a 0 before the digit
}

$dp_gbx = $bundle->getDiscountedPriceGBX(); // Get discounted price in pence

$dp_pounds = intdiv($dp_gbx, 100); // Get pounds in DP
$dp_pence = $dp_gbx % 100; // Get pence in DP

// Convert to strings
$dp_pounds_str = strval($dp_pounds); 
$dp_pence_str = strval($dp_pence);

// Make sure there are two digits in pence
if (strlen($dp_pence_str) == 1) {
    $dp_pence_str = '0' . $dp_pence_str;
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

?>

<section class="issue-form">
    <nav class="nav">
        <ul class="nav-left">
            <li>
                <a class="button button--rounded" href="/view_reservation.php?id=<?php echo $reservationID; ?>">
                    <img src="/assets/icons/arrow_back.png" height="24px"></img>
                    <span>Reservation</span>
                </a>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </nav>
    <h1>Create an Issue</h1>
    <p class="error-text"></p>
    <div class="issue-form__field">
        <label for="bundle">Bundle</label>
        <span id="bundle"><?php echo $bundleTitle;?></span>
    </div>
    <div class="issue-form__field">
        <label for="seller">Seller</label>
        <span id="seller"><?php echo $bundleSellerName;?></span>
    </div>
    <div class="issue-form__field">
        <label for="price">Price</label>
        <div id="price"><span id="discount-price">£<?php echo $dp_pounds_str . "." . $dp_pence_str ?></span><span id="rr-price">(RRP £<?php echo $rrp_pounds_str . "." . $rrp_pence_str ?>)</span></div>
        
    </div>
    <div class="issue-form__field">
        <label for="issue-title">Issue Title</label>
        <div class="textbox" data-type="text" data-id="issue-title" id="issue-title-textbox"></div>
    </div>
    <div class="issue-form__field">
        <label for="issue-text">Issue Text</label>
        <textarea class="textarea" id="issue-text"></textarea>
    </div>
    <div class="issue-form__btns">
        <button type="button" class="button round green" id="submit-btn">Submit</button>
        <button type="button" class="button round" id="clear-btn">Clear</button>
        <button type="button" class="button round red" id="cancel-btn">Cancel</button> 
    </div>
</section>
<script src="/assets/js/issue_form.js"></script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>
