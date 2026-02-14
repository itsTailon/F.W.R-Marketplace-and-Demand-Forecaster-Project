

<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Reservation;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
require_once 'partials/head.php';
$acc = Authenticator::getCurrentUser();




?>


<h3>Account ID: <?php echo $acc->getUserID(); ?></h3>
<h3>Account Type: <?php echo $acc->getAccountType(); ?></h3>
<h3>Account Email: <?php echo $acc->getEmail(); ?></h3>