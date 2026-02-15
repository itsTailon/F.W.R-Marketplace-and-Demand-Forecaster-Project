<?php


use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\Customer;
use TTE\App\Model\Account;
use TTE\App\Model\Reservation;



$reservationID = filter_var($_GET['id'], FILTER_VALIDATE_INT);
 

$user = Authenticator::getCurrentUser();
$userID = $user->getUserID();


if(!Reservation::existsWithID($reservationID)){
    die("Reservation not found.");
}



$reservation = Reservation::load($reservationID);

$bundleID = $reservation->getBundleID();
$bundle = Bundle::load($bundleID);


if($reservation->getPurchaserID() != $userID) {
    die("Reservation not found.");
}


?>


<div class="view-reservation-wrapper">
    <h1><?php echo $bundle->getTitle() ?></h1>
    <p><i>reservation date posted...</i></p>

    <div class="view-reservation-info">
        <div class="view-reservation-info-box">
            <h3 class="view-reservation-info-box-info">Collecting at XX:XX on XX/XX/XXXX</h3>
            <h3 class="view-reservation-info-box-info">Code: <?php echo $reservation->getClaimCode() ?></h3>
            <h3 class="view-reservation-info-box-price">£<?php echo number_format($bundle->getDiscountedPriceGBX() / 100, 2); ?></h3>
        </div>
        <ul class="view-reservation-buttons">
            <li>
                <a>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                    <span>Cancel</span>
                </a>
            </li>
        </ul>
    </div>


    <div class="view-reservation-description">
        <h5>Allergens listed ....</h5>
        <br>
        <p>bundle description</p>
    </div>
</div>


