<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Dashboard";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('ERROR: Not logged in! <br> TODO: redirect to login page');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar

require_once 'partials/dashboard/dashboard_sidebar.php';



?>

<div class="dashboard-wrapper" style="height: calc(100vh - 256px);">
    <div class="dashboard" style="display: flex; flex-direction: column; gap: 32px; text-align: center; align-items: center; justify-content: center; height: 100%;">
        <h1>Under Construction</h1>
        <h2>This feature is coming soon.</h2>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

