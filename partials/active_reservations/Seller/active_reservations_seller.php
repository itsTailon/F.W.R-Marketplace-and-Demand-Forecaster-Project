<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Reservation;
use TTE\App\Model\Bundle;


$acc = Authenticator::getCurrentUser();

$reservations = Reservation::getAllReservationsForUser($acc->getUserID(), 'seller');


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
    <div class="active-reservations"></div>
    <?php if (!$reservations): ?>
        <h1>No Active Reservations</h1>
    <?php else: ?>
    <h1>Active Reservations</h1>   
    <div class="active-reservations-list-wrapper">
        <ul class="active-reservations-list">
            <?php foreach ($reservations as $r): ?>

                <?php
                    $bundleID = $r['bundleID'];
                    $bundle = Bundle::load($bundleID);
                    $reservationID =$r['reservationID'];
                    if($r['reservationStatus'] != 'active'){
                        continue;
                    }
                    
                ?>
                <li>
                    <h1 class="active-reservations-bundle-name"><?php echo $bundle->getTitle() ?></h1>
                    <p class="active-reservations-bundle-description">Bundle description: <i><?php echo $bundle->getDetails() ?></i></p>
                    <p class="active-reservations-bundle-date"><i>Bundle Date posted</i></p>

                    <nav class="active-reservations-bundle-nav">
                        <ul>
                            <li><h2>£<?php echo number_format($bundle->getDiscountedPriceGBX() / 100, 2); ?></h2></li>
                            <li><a class="active-reservations-bundle-nav-view" href="/view_reservation.php?id=<?php echo $reservationID ?>">View</a></li>
                            <li><a class="active-reservations-bundle-nav-view" href="/edit_bundle.php?id=<?php echo $bundleID ?>">Edit</a></li>
                            <li><a class="active-reservations-bundle-nav-cancel" data-res-id="<?php echo $reservationID ?>">Cancel</a></li>
                        </ul>
                    </nav>

                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>

<script>
    $('.active-reservations-bundle-nav-cancel').on('click', function(e) {
        e.preventDefault();
        const reservationID = $(this).data('res-id');
        console.log(reservationID);
        $.ajax({
            type: 'DELETE',
            url: '/backend/API/Model/consumerReservation.php',
            data: {reservationID: reservationID},
            success: function() {
                // redirect
                window.location.href = '/active_reservations.php';
            },
            error: function(err) {
                console.log('Failed to Cancel: ' + err.status);
            }
        });

    });

    

</script>