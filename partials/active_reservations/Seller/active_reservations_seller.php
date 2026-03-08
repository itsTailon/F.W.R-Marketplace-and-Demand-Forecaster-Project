<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Reservation;
use TTE\App\Model\Bundle;


$acc = Authenticator::getCurrentUser();

$statuses = ["active", "completed", "no-show", "cancelled"];

$status = $_GET['status'];
if (!in_array($status, $statuses)) {
    $status = "active";
}

$allReservations = Reservation::getAllReservationsForUser($acc->getUserID(), 'seller');

$reservations = [];

foreach ($allReservations as $r) {
    if ($r["reservationStatus"] == $status) {
        $reservations[sizeof($reservations)] = $r;
    }
}

function generateReservationList($res): void {
    global $status;
    echo "<div class=\"active-reservations-list-wrapper\">
            <ul class=\"active-reservations-list\">";
                foreach ($res as $r) {
                    $bundleID = $r['bundleID'];
                    $bundle = Bundle::load($bundleID);
                    $reservationID =$r['reservationID'];
                    echo "<li>
                            <h1 class=\"active-reservations-bundle-name\">" . $bundle->getTitle() . "</h1>
                            <p class=\"active-reservations-bundle-description\">Bundle description: <i>" . $bundle->getDetails() . "</i></p>
                            <!--                    <p class=\"active-reservations-bundle-date\"><i>Bundle Date posted</i></p>-->

                            <nav class=\"active-reservations-bundle-nav\">
                                <ul>
                                    <li><h2>£" . number_format($bundle->getDiscountedPriceGBX() / 100, 2) . "</h2></li>
                                    <li><a class=\"active-reservations-bundle-nav-view\" href=\"/view_reservation.php?id=" . $reservationID . "\">View</a></li>
                                    <li><a class=\"active-reservations-bundle-nav-view\" href=\"/edit_bundle.php?id=" . $bundleID . "\">Edit</a></li>";
                                    if ($status == "active") {
                                        echo "<li><a class=\"active-reservations-bundle-nav-cancel\" data-res-id=" . $reservationID . ">Cancel</a></li>";
                                    }
                            echo "</ul>
                        </nav>
                    </li>";
                }

            echo "</ul>
        </div>";
}

function getHeaderStatus($s) {
    switch ($s) {
        case "active":
            echo "Active";
            break;
        case "completed":
            echo "Completed";
            break;
        case "no-show":
            echo "No-Show";
            break;
        case "cancelled":
            echo "Cancelled";
            break;
    }    
}

?>

<div class="active-reservations-wrapper">
    <nav class="active-reservations-nav">
        <ul class="active-reservations-nav-left">
            <li>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="active-reservations__tabbar">
        <button class="tab start <?php if ($status == "active") { echo "selected"; }?>" id="active-tab">Active</button>
        <button class="tab <?php if ($status == "completed") { echo "selected"; }?>" id="completed-tab">Completed</button>
        <button class="tab <?php if ($status == "no-show") { echo "selected"; }?>" id="no-show-tab">No-Show</button>
        <button class="tab end <?php if ($status == "cancelled") { echo "selected"; }?>" id="cancelled-tab">Cancelled</button>
    </div>
    <div class="active-reservations"></div>
    <?php if (!$reservations): ?>
        <h1>No <?php getHeaderStatus($status); ?> Reservations</h1>
    <?php else: ?>
        <h1><?php getHeaderStatus($status); ?> Reservations</h1>
        <?php generateReservationList($reservations); ?>
    <?php endif; ?>
</div>

<script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
<script src="/assets/js/reservation_seller.js"></script>
