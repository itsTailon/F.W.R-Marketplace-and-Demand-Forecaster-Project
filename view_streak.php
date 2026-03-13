<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

use TTE\App\Model\ImpactMetric;
use TTE\App\Model\Seller;

require 'partials/head.php';

// If user is not logged in, briefly display an error
// and then redirect to login
if (!Authenticator::isLoggedIn()) {
    echo <<<XYZ
    <p>ERROR: Not logged in!</p>
    <script>
        function redirectToLogin() {
            location.href = "/login.php"
        }

        setTimeout(redirectToLogin, 3000);

    </script>
    XYZ;
    die();
}

// User is not a customer, so redirect to 404
if (!(Authenticator::getCurrentUserSubclass() instanceof Customer)) {
    header('Location: /404.php');
    die();
}

// User is a customer.
$customer = Authenticator::getCurrentUserSubclass();

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

?>

<div class="view-streak-container">
    <div class="view-streak">
        <p class="text-middle streak__header">Your streak</p>
        <br>
        <p class="text-middle streak__display"><img src="/assets/icons/streak_icon.png"><span id="weeks">0</span></p>
        <br>

        <div class="calendar">
            <div class="calendar__menu">
                <button type="button" class="button nav" id="prev-btn"><img src="/assets/icons/arrow_back.png"></button>
                <span class="calendar__menu__header"></span>
                <button type="button" class="button nav" id="next-btn"><img src="/assets/icons/arrow_forward.png"></button>
            </div>
            <table class="calendar__table" id="calendar"></table>
        </div>

        <div class="view-streak-impact">
            <h2 class="view-streak-impact__heading">Your Personal Impact</h2>

            <div class="view-streak-impact__stats">
                <?php
                    // Get personal impact metrics
                    $bundlesRescued = $customer->getImpactMetric(ImpactMetric::Bundles_Collected);
                    $co2Saved = $customer->getImpactMetric(ImpactMetric::CO2_Saved);
                ?>

                <?php if ($bundlesRescued > 0) : ?>
                    <span class="view-streak-impact__stats__single-stat">Since joining, you have collected <b><?php echo $bundlesRescued; ?></b> bundle<?php echo $bundlesRescued > 1 ? 's' : ''; ?>, saving an estimated <b><?php echo $co2Saved; ?>kg</b> of CO2.</span>
                <?php else: ?>
                    <span class="view-streak-impact__stats__single-stat">You have not collected any bundles yet.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
<script src="/assets/js/view_streak.js"></script>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

