<?php
require 'vendor/autoload.php';

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\Seller;

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure that the user is logged-in
if (!Authenticator::isLoggedIn()) {
    // Not logged-in, so redirect to login page
    header('Location: login.php');
}

// Ensure that bundle ID was passed in request
if (!isset($_GET['id'])) {
    // TODO: Remove die call. Instead, redirect to 404 or somewhere else.
    die("ERROR: No ID given. (TODO: Redirect to 404 or elsewhere)");
}

// Get bundle ID
$bundleID = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Check ensure that bundle ID given is valid (i.e. it is an integer and corresponds to an actual record)
if (!is_int($bundleID) || !Bundle::existsWithID($bundleID)) {
    // TODO: Remove die call. Instead, redirect to 404 page
    die("ERROR: Invalid bundle ID (TODO: Redirect to 404)");
}

// Instantiate Bundle object to get bundle data
$bundle = Bundle::load($bundleID);

// Define document (i.e. tab) title
$DOCUMENT_TITLE = $bundle->getTitle();

// Include page head
require_once 'partials/head.php';

// Include header bar
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

?>

<div class="single-bundle-container">
    <div class="single-bundle-wrapper">
        <div class="bundle-dashboard-buttons">
            <!-- TODO: Add symbols  -->
            <a href="" class="button button--rounded">Listings</a>
            <a href="dashboard.php" class="button button--rounded">Home</a>
        </div>

        <div class="bundle-view">

            <div class="bundle-view__main">
                <img src="" alt="" class="bundle-view__img">
                <div class="bundle-view__info">
                    <h1 class="bundle-view__title"><?php echo $bundle->getTitle(); ?></h1>

                    <?php
                    // TODO: Replace w/ CurrencyTools function

                    $lhs = intdiv($bundle->getDiscountedPriceGBX(), 100);
                    $rhs = $bundle->getDiscountedPriceGBX() % 100;

                    $rhsStr = $rhs < 10 ? "0$rhs" : "$rhs";

                    $priceStr = "$lhs.$rhsStr";

                    ?>

                    <span class="bundle-view__seller"><?php echo (Seller::load($bundle->getSellerID()))->getName(); ?></span>
                    <span class="bundle-view__date">Posted on DATE at TIME</span>
                    <div class="bundle-view__price">
                        <span>£<?php echo $priceStr; ?></span>
                        <?php
                            $user = Authenticator::getCurrentUserSubclass();
                            if ($user instanceof Seller && $bundle->getSellerID() == $user->getUserID()) {
                                ?><a href="" class="bundle-view__edit-btn button button--rounded button--green">Edit</a><?php
                            } else if ($user instanceof \TTE\App\Model\Customer) {
                                ?><a href="" class="bundle-view__reserve-btn button button--rounded button--green">Reserve</a><?php
                            }
                        ?>
                    </div>
                </div>
            </div>

            <div class="bundle-view__desc-wrapper">
                <span class="bundle-view__allergens">Allergens listed: XX, XX</span>
                <p class="bundle-view__desc"><?php echo $bundle->getDetails(); ?></p>
            </div>

        </div>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

