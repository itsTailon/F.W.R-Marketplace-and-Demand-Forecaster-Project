<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

use TTE\App\Model\Seller;

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

$currentUser = $_SESSION['currentUser'];

// Function to show if bundle is not found or doesn't belong to seller
// For security reasons, we won't say which one of two possibilities
function showBundleUnavailable() {
    // Display error page that redirects to dashboard after 3 seconds
    echo <<<XYZ
    <p>ERROR: Bundle not found or does not belong to seller.</p>
    <script>
        function redirectToDashboard() {
            location.href = "/dashboard.php"
        }

        setTimeout(redirectToDashboard, 3000);

    </script>
    XYZ;
    die();
}

try {
    $bundle = Bundle::load($_GET['id']); // Get the ID passed as a parameter to the URL
    // If the current user ID is not the same as the bundle seller ID
    if ($bundle->getSellerID() != $currentUser->getUserID()) {
        showBundleUnavailable(); // Show the error page
    }
} catch (DatabaseException $e) { // If the bundle does not exist
    showBundleUnavailable(); // Also show the error page
}

$rrp_gbx = $bundle->getRrpGBX(); // Get RRP in pence

$rrp_pounds = intdiv($rrp_gbx, 100); // Get the pounds of RRP by integer dividing by 100
$rrp_pence = $rrp_gbx % 100; // Get the remaining pence by the modulo operation

// Convert both numbers to strings
$rrp_pounds_str = strval($rrp_pounds); 
$rrp_pence_str = strval($rrp_pence);

// If there is only 1 digits in pence (we want pence to be two digits)
if (strlen($rrp_pence_str) == 1) {
    $rrp_pence_str = '0' . $rrp_pence_str; // Add a 0 before the digit
}

$dp_gbx = $bundle->getDiscountedPriceGBX(); // Get discounted price in pence

$dp_pounds = intdiv($dp_gbx, 100); // Get pounds in DP
$dp_pence = $dp_gbx % 100; // Get pence in DP

// Convert to strings
$dp_pounds_str = strval($dp_pounds); 
$dp_pence_str = strval($dp_pence);

// Make sure there are two digits in pence
if (strlen($dp_pence_str) == 1) {
    $dp_pence_str = '0' . $dp_pence_str;
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<section class="edit-form">
    <h1>Editing "<span id="bundle-name"><?php
        echo $bundle->getTitle(); // Display title
    ?></span>"</h1>
    <p class="error-text"></p>
    <br>
    <div class="edit-form__field">
        <label for="name">Name</label>
        <div class="textbox" data-type="text" data-id="name" id="name-textbox" data-value="<?php
        echo $bundle->getTitle(); // Insert title in name field
    ?>"></div>
    </div>
    <br>
    <div class="edit-form__field">
        <label for="description">Description</label>
        <textarea class="textarea" id="description"><?php
        echo $bundle->getDetails(); // Insert details in description field
    ?></textarea>
    </div>
    <br>
    <button type="button" class="button round red" id="add-allergen-btn">Add Allergen</button>
    <br>
    <ul class="allergen-list">
    </ul>
    <br>
    <div class="edit-form__field">
        <label for="rrp">Recommended Retail Price</label>
        <div class="textbox" data-type="text" data-id="rrp" data-label="Price in £" id="rrp-textbox" data-value="<?php
        print($rrp_pounds_str . '.' . $rrp_pence_str); // Format RP as £XX.XX
        ?>"></div>
    </div>
    <div class="edit-form__field">
        <label for="discount-price">Discounted Price</label>
        <div class="textbox" data-type="text" data-id="discount-price" data-label="Price in £" id="discount-price-textbox" data-value="<?php
        print($dp_pounds_str . '.' . $dp_pence_str); // Format DP as £XX.XX
        ?>"></div>
    </div>
    <br>
    <div class="edit-form__btns">
        <button type="button" class="button round green" id="submit-btn">Submit</button>
        <button type="button" class="button round" id="clear-btn">Clear</button>
        <button type="button" class="button round red" id="delete-btn">Delete</button>    
    </div>
</section>

<script src="/assets/js/components/text-inputs.js"></script>
<script src="/assets/js/bundle_form.js"></script>
<script src="/assets/js/edit.js"></script>
