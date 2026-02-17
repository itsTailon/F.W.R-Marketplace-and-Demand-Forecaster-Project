<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

// Define document (i.e. tab) title

// Include page head
require_once 'partials/head.php';


$acc = Authenticator::getCurrentUser();
$accType = $acc->getAccountType();

if($accType == 'seller') {
    require_once 'partials/dashboard/seller/dashboard_sidebar.php';
}
else if ($accType == 'customer') {
    require_once 'partials/dashboard/customer/dashboard_sidebar.php';
}


?>