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
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Ensure that bundle ID was passed in request
if (!isset($_GET['id'])) {
    header('Location: 404.php');
    die();
}

// Get bundle ID
$bundleID = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Check ensure that bundle ID given is valid (i.e. it is an integer and corresponds to an actual record)
if (!is_int($bundleID) || !Bundle::existsWithID($bundleID)) {
    header('Location: 404.php');
    die();
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

<input type="hidden" id="bundleID" value="<?php echo $bundleID; ?>">
<input type="hidden" id="purchaserID" value="<?php echo Authenticator::getCurrentUser()->getUserID(); ?>">


<div class="single-bundle-container">
    <div class="single-bundle-wrapper">
        <div class="bundle-dashboard-buttons">
            <!-- TODO: Add symbols  -->
            <a href="browse.php" class="button button--rounded">Listings</a>
            <a href="dashboard.php" class="button button--rounded">Home</a>
        </div>

        <div class="bundle-view">

            <div class="bundle-view__main">
                <img src="/assets/img/bundle_placeholder.jpg" alt="" class="bundle-view__img">
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
                    <span class="bundle-view__date"></span>
                    <span class="bundle-view__quantity">
                        <?php if ($bundle->getQuantity() > 0) : // Bundle in in stock ?>
                            <b>In stock:</b> <?php echo $bundle->getQuantity(); ?>
                        <?php else : // Bundle is NOT in stock ?>
                            <i>Out of stock</i>
                        <?php endif; ?>
                    </span>

                    <span class="bundle-view__pickup-window"><b>Pickup:</b> <?php echo $bundle->getPickupWindow(); ?> any day we're open</span>

                    <div class="bundle-view__price">
                        <span>£<?php echo $priceStr; ?></span>
                        <?php
                            $user = Authenticator::getCurrentUserSubclass();
                            if ($user instanceof Seller && $bundle->getSellerID() == $user->getUserID()) {
                                ?><a href="edit_bundle.php?id=<?php echo $bundle->getID(); ?>" class="bundle-view__edit-btn button button--rounded button--green">Edit</a><?php
                            } else if ($user instanceof \TTE\App\Model\Customer) {
                                if ($bundle->getStatus() == \TTE\App\Model\BundleStatus::OnSale) {
                                    ?>
                                    <button id="reserve-btn" class="bundle-view__reserve-btn button button--rounded button--green">Reserve</button>
                                    <script src="assets/js/bundle_reserve.js"></script>
                                    <?php
                                } else {
                                    ?>
                                    <span>RESERVED</span>
                                    <?php
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>

            <div class="bundle-view__desc-wrapper">
                <?php
                if (!empty($bundle->getAllergens())) {
                    ?>
                        <span class="bundle-view__allergens">
                        Allergens listed:
                        <?php
                        $allergens = $bundle->getAllergens();

                        for ($i = 0; $i < count($allergens); $i++) {
                            echo $allergens[$i];

                            if ($i != count($allergens) - 1) {
                                echo ', ';
                            }
                        }
                        ?>
                        </span>
                    <?php
                }
                ?>
                <p class="bundle-view__desc"><?php echo $bundle->getDetails(); ?></p>
            </div>

            <div class="bundle-view__prediction">
            <?php
            $user = Authenticator::getCurrentUserSubclass();
            if ($user instanceof Seller && $bundle->getSellerID() == $user->getUserID()) {
                $prediction = \TTE\App\Model\Forecast::getProductionRecommendation($bundle);
                $collectedNumber = $prediction[0];
                $noShow = $prediction[1];
                $quantity = $prediction[2];
                $time = $prediction[3];

                $adjustment = (int)($collectedNumber/7) - $bundle->getQuantity();

                if($collectedNumber == 0 && $noShow == 0) {
                    ?>
                    <p> There are no recorded instances of similar bundles at this time for this account </p>
                    <?php
                } else {
                    ?>
                    <p><?php echo $collectedNumber?> of these bundles are collected each week, and <?php echo $noShow?> are missed.</p>
                    <?php

                    if($adjustment = 0) {
                        ?>
                        <p> A sufficient number of this bundle type has been posted </p>
                        <?php
                    } elseif ($adjustment < 0) {
                        $adjustment = $adjustment * -1;
                        ?>
                        <p> It is recommended that <?php echo $adjustment?> less of this type of bundle should be listed</p>
                        <?php
                    } elseif ($adjustment > 0) {
                        ?>
                        <p> It is recommended that <?php echo $adjustment?> more of this type of bundle should be listed</p>
                        <?php
                    }

                    if($time != null){
                        ?>
                        <p> The most popular time for pickup is <?php echo $time?></p>
                        <?php
                    } else {
                        ?>
                        <p> No bundles of this type have been collected yet</p>
                        <?php
                    }
                }
                ?>
                    <div class="bundle-view__recommendation"
                <?php
            }
            ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

