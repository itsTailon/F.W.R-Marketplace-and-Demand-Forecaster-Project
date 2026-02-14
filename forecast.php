<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Customer;

require 'partials/head.php';

session_start();

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

<h1 class="text-middle">Forecasting</h1>
<br>

<canvas class="graph-2d" id="graph" width="900" height="500"></canvas>

<script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
<script src="/assets/js/forecast.js"></script>
