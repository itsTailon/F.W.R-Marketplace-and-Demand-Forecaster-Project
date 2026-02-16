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


if($bundle->getSellerID() != $userID) {
    die("Reservation not found.");
}


$purchaserID = $reservation->getPurchaserID();
$purchaserAcc = Customer::load($purchaserID);

?>


<div class="view-reservation-wrapper">
    <h1><?php echo $bundle->getTitle() ?></h1>
<!--    <p><i>reservation date posted...</i></p>-->

    <div class="view-reservation-info">
        <div id="status-message" style="color: red;"></div>
        <div class="view-reservation-info-box">
<!--            <h3 class="view-reservation-info-box-info">Collecting at XX:XX on XX/XX/XXXX</h3>-->
            <h3 class="view-reservation-info-box-info">Email: <?php echo $purchaserAcc->getEmail() ?></h3>
            <h3 class="view-reservation-info-box-price">£<?php echo number_format($bundle->getDiscountedPriceGBX() / 100, 2); ?></h3>
        </div>
        <form class="view-reservation-form" method="POST" action="/backend/API/Model/sellerReservation.php">
             <input type="hidden" name="reservationID" value="<?php echo $reservationID; ?>">
            <input type="text" class="view-reservation-form-text" placeholder="Enter code..." id="code" name="claimCode">
            <button type="submit" class="view-reservation-form-submit" id="submit">
                
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                <span>Collected</span>
                
                
            </button>
        </form>
        <ul class="view-reservation-buttons">
            <li>
                <a>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M324-111.5Q251-143 197-197t-85.5-127Q80-397 80-480t31.5-156Q143-709 197-763t127-85.5Q397-880 480-880t156 31.5Q709-817 763-763t85.5 127Q880-563 880-480t-31.5 156Q817-251 763-197t-127 85.5Q563-80 480-80t-156-31.5ZM480-160q54 0 104-17.5t92-50.5L228-676q-33 42-50.5 92T160-480q0 134 93 227t227 93Zm252-124q33-42 50.5-92T800-480q0-134-93-227t-227-93q-54 0-104 17.5T284-732l448 448ZM480-480Z"/></svg>
                    <span>No Show</span>
                </a>
            </li>
            <li>
                <button id="cancel-button">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                    <span>Cancel</span>
                </button>
            </li>
<!--            <li>-->
<!--                <a>-->
<!--                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v200h-80v-40H200v400h280v80H200Zm0-560h560v-80H200v80Zm0 0v-80 80ZM560-80v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-300L683-80H560Zm300-263-37-37 37 37ZM620-140h38l121-122-18-19-19-18-122 121v38Zm141-141-19-18 37 37-18-19Z"/></svg>-->
<!--                    <span>Reschedule</span>-->
<!--                </a>-->
<!--            </li>-->
        </ul>
    </div>


    <div class="view-reservation-description">
        <?php
        if (!empty($bundle->getAllergens())) {
            ?>
            <strong>
                Allergens listed:
                <?php
                $allergens = $bundle->getAllergens();

                for ($i = 0; $i < count($allergens); $i++) {
                    echo $allergens[$i];

                    if ($i != count($allergens) - 1) {
                        echo ', ';
                    }
                }
                ?>
            </strong>
            <?php
        }
        ?>
        <br>
        <p><?php echo $bundle->getDetails(); ?></p>
    </div>
</div>

<script>
$('.view-reservation-form').on('submit', function (e) {
    e.preventDefault();

    const statusMessage = document.getElementById('status-message');

    $.ajax({
        type: 'POST',
        url: '/backend/API/Model/sellerReservation.php',
        data: $(this).serialize(),
    success: function () {
        alert("Bundle successfully marked as collected!");
        window.location.href = '/active_reservations.php';
    },
    error: function (err) {
        statusMessage.textContent = 'Incorrect claim code.';
    }
    });
});
</script>



<script>
    const cb = document.getElementById('cancel-button');
    const statusMessage =document.getElementById('status-message');

    cb.addEventListener('click', function() {
        $.ajax({
            type: 'DELETE',
            url: '/backend/API/Model/sellerReservation.php',
            data: {reservationID: <?php echo $reservationID ?>},
            success: function() {
                // redirect
                window.location.href = '/active_reservations.php';
            },
            error: function(err) {
                statusMessage.textContent = 'Failed to Cancel: ' + err.status;
            }
        });
    });
    

</script>

<!-- <script>
  const code = document.getElementById('code');
  const btn  = document.getElementById('submit');

  code.addEventListener('input', function() {
    code.value = code.value.replace(/\D/g, '').slice(0, 4);

    btn.disabled = code.value.length !== 4;
  });

</script> -->