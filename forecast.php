<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Customer;
use TTE\App\Model\Seller;

require 'partials/head.php';

// Redirect user to login page if they are not logged in.
if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Ensure that user is a Seller (Seller-only page)
$acc = Authenticator::getCurrentUserSubclass();
if (!($acc instanceof Seller)) {
    header('Location: /dashboard.php');
    die('');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<br>
<h1 class="text-middle">Forecasting</h1>
<br>

<div class="forecast-variables">
    <div class="forecast-variables__var">
        <label for="day">Day</label>
        <select id="day" disabled>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>
    </div>
    <div class="forecast-variables__var">
        <label for="category">Category</label>
        <select id="category">
            <option value="groceries">Groceries</option>
            <option value="cakes">Cakes</option>
            <option value="meals">Meals</option>
            <option value="brownies">Brownies</option>
            <option value="savoury_pastries">Savoury Pastries</option>
            <option value="sandwiches">Sandwiches</option>
            <option value="sweet_pastries">Sweet Pastries</option>
        </select>
    </div>
    <div class="forecast-variables__var">
        <label for="weather">Weather</label>
        <select id="weather">
            <option value="">All</option>
            <option value="sunny">Sunny</option>
            <option value="cloudy">Cloudy</option>
            <option value="rain">Rain</option>
            <option value="snow">Snow</option>
        </select>
    </div>
    <div class="forecast-variables__var">
        <label>Time</label>
        <span><input type="number" min="0" max="23" value="08" id="start-hr">:<input type="number" min="0" max="59" value="00" id="start-min"> to <input type="number" min="0" max="23" value="20" id="end-hr">:<input type="number" min="0" max="59" value="00" id="end-min"></span>
    </div>
    <div class="forecast-variables__var">
        <label>Discount</label>
        <span><input type="number" min="0" max="100" value="0" id="min-discount">% to <input type="number" min="0" max="100" value="100" id="max-discount">%</span>
    </div>
    <button type="button" class="button" id="update-btn">Update</button>
</div>

<canvas class="graph-2d" id="graph" width="900" height="500"></canvas>

<script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
<script src="/assets/js/forecast.js"></script>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

