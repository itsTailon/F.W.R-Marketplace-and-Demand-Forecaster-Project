<?php
include "vendor/autoload.php";

use TTE\App\Model\Customer;
use TTE\App\Model\Seller;

$error = false;

try {
    Seller::create(["email" => "john.doe@testwassuccessful.com", "name" => "Some Corner Shop", "password" => "password", "address" => "Middle of Nowhere"]);
} catch (\TTE\App\Model\DatabaseException $e) {
    $error = true;
    echo $e->getMessage();
}

try {
    Customer::create(["email" => "john.doe.2@testwassuccessful.com", "username" => "John Doe", "password" => "password"]);
} catch (\TTE\App\Model\DatabaseException $e) {
    $error = true;
    echo $e->getMessage();
}

if (!$error) {
    echo "All seems to be well; check the database to see if your people got registered.\n";
}
