<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Helpers\TimeTools;
use TTE\App\Model\Seller;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\Bundle;

// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Browse Bundles";

// Include page head
require_once 'partials/head.php';

// TODO: Replace this with graceful redirect to login page
// (Temporary code) Halt rendering if user not logged in
if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('ERROR: Not logged in!');
}

if(!isset($_GET['location'])){
    $_GET['location']='';
}


// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <form id="searchform" method="GET">
            <div id="basic-search">
                <h2>Search for an item</h2><br>
                <article id = "basic-search-parallel">
                    <input type="text" name="searchbar" id="searchbar" class="searchformelem">
                    <input class="button searchformelem" id="searchsubmitbutton" type="submit" value="Search">
                </article>
            </div>

            <div id="advanced-filters">
                <h3>Advanced filters</h3>
                <article>
                    <label for="location" id="location-label">Location:</label><br>
                    <input type="text" name="location" id="location" class="searchformelem"/>
                </article>
                <article>
                    <label for="earliest-pickup" id="earliest-pickup-label">Earliest pickup:</label>
                    <select id="earliest-pickup" name="earliest-pickup" class="dropdown">
                        <?php
                        echo '<option selected value = "0">0:00</option>';

                        for ($i = 1; $i < 24; $i++) {
                            echo '<option value="' . $i . '">' . $i . ':00</option>';
                        }
                        ?>
                    </select>
                </article>
                <article>
                    <label for="latest-pickup" id="latest-pickup-label">Latest pickup:</label>
                    <select id="latest-pickup" name="latest-pickup" class="dropdown">
                        <?php
                        for ($i = 1; $i < 24; $i++) {
                            $hourAsText = $i . ":00";
                            echo '<option value="' . $i . '">' . $i . ':00</option>';
                        }

                        echo '<option selected value = "24">24:00</option>"';
                        ?>
                    </select>
                </article>
            </div>
        </form>

        <div id = "searchresults">
        <?php
        $query = $_GET['searchbar'] ?? '';

        $results = Bundle::searchBundles($query);

        $earliestPickupDesired = 3600 * ($_GET['earliest-pickup'] ?? 0); // Time measured in seconds since midnight
        $latestPickupDesired = 3600 * ($_GET['latest-pickup'] ?? 24); // Ditto

        $nItemsDisplayed = 0; // The number of items that have been displayed

        if ($latestPickupDesired <= $earliestPickupDesired) {
            echo "<p>Latest pickup time before or equal to earliest pickup time; no results will be shown.</p>\n";
        } else for ($i = 0; $i < count($results); $i++) {
            $seller = Seller::load($results[$i]->getSellerID());

            if ($_GET['location'] && $_GET['location'] != $seller->getAddress()) {
                continue;
            }

            if ($earliestPickupDesired != 0 || $latestPickupDesired != 24 * 3600) { // Only bother doing string manipulation and stuff if the values are actually set
                $pickupWindow = $results[$i]->getPickupWindow();

                if (!TimeTools::verifyTimeSlotStringFormat($pickupWindow)) {
                    echo "<p>Time slot for bundle '" . $results[$i]->getTitle() . "' with ID " . $results[$i]->getUserID() . " stored in invalid format in database.\n</p>";
                }

                $times = explode("-", $pickupWindow); // Get the times that are available
                $earliestPickupAvailableHHMM = TimeTools::parseTimeString($times[0]); // Get the earliest available pickup
                $latestPickupAvailableHHMM = TimeTools::parseTimeString($times[1]); // Get the latest available pickup
                $earliestPickupAvailable = TimeTools::timeAsSecondsFromMidnight($earliestPickupAvailableHHMM[0], $earliestPickupAvailableHHMM[1]); // Convert to int
                $latestPickupAvailable = TimeTools::timeAsSecondsFromMidnight($latestPickupAvailableHHMM[0], $latestPickupAvailableHHMM[1]); // Convert to int

                if ($earliestPickupDesired >= $latestPickupAvailable || $latestPickupDesired <= $earliestPickupAvailable) continue; // Filter by pickup window
            }

            $results[$i]->display();
            $nItemsDisplayed++; // Only increment the number of items that have been displayed once we have actually displayed an item
        }

        if ($nItemsDisplayed == 0) echo "<i>No results found</i>\n";
        ?>
        </div>
    </div>
</div>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>