

<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Reservation;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
require_once 'partials/head.php';
$acc = Authenticator::getCurrentUser();

// $seller = Seller::create([
//   "email" => "test@gmail.com",
//   "password" => "testingPassword123",
//   "name" => "Tyrone",
//   "address" => "Grove Street"
// ]);

$fields = [
  "sellerID" => 1,
  "bundleStatus" => BundleStatus::Available,
  "title" => "Test Bundle " . rand(1,20),
  "details" => "BUNDLE",
  "rrp" => 25,
  "discountedPrice" => 15,
];

$bundle = Bundle::create($fields);



$rfields = [
  "bundleID" => $bundle->getID(),
  "purchaserID" => $acc->getUserID(),
  "status" => ReservationStatus::Active
];

Reservation::create($rfields);


?>