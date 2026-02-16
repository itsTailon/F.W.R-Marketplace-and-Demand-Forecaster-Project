<?php
use TTE\App\Auth\Authenticator;
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

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <form id="searchform" method="GET">
            <div class="textbox textbox--size-fill" data-type="text" data-label="Search" data-name="searchbar" data-id="searchbar" id="searchbar-textbox"></div>
            <input class="button" id="searchsubmitbutton" type="submit" value="Search">
        </form>


        <div id = "searchresults">
        <?php
        $query = $_GET['searchbar'] ?? '';

        $results = Bundle::searchBundles($query);

        for ($i = 0; $i < count($results); $i++) {
            $results[$i]->display();
        }
        ?>
        </div>
    </div>
</div>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>