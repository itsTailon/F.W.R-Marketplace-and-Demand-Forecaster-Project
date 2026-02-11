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
// if (!Authenticator::isLoggedIn()) {
//    die('ERROR: Not logged in! <br> TODO: redirect to login page');
// }

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<form id = "searchform" method = "GET">
    <label for = "searchbar">
    <input id = "searchbar" name = "searchbar">
    <input type = "submit" value = "Search">
</form>

<?php
if (isset($_GET['searchbar'])) {
    $results = Bundle::searchBundles($_GET['searchbar']);

    for ($i = 0; $i < count($results); $i++) {
        echo "Displaying an item";
        $results[$i]->display();
    }
} else {
    echo "<div><i>Results will be shown here</i></div>";
}

// Include page footer and closing tags
require_once 'partials/footer.php';
?>
