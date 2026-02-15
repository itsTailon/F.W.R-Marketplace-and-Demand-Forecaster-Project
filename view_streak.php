<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

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

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

?>

<p class="text-middle streak__header">Your streak</span></p>
<br>
<p class="text-middle streak__display"><span id="flame">🔥 </span><span id="weeks">2</span> weeks</p>
<br>

<div class="calendar">
    <div class="calendar__menu">
        <button type="button" class="button nav" id="prev-btn"><img src="/assets/icons/arrow_back.png"></button>
        <span class="calendar__menu__header"></span>
        <button type="button" class="button nav" id="next-btn"><img src="/assets/icons/arrow_forward.png"></button>
    </div>
    <table class="calendar__table" id="calendar"></table>
</div>


<script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
<script src="/assets/js/view_streak.js"></script>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

