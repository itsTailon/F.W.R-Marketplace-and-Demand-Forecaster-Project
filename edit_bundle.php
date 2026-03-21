<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Bundle;
use TTE\App\Model\Category;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

use TTE\App\Model\Seller;


require 'partials/head.php';


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

$categoryList = Category::getCategoryList();
$myCategory = $bundle->getCategory();
$quantity = $bundle->getQuantity();

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
    <div class="edit-form__field">
        <?php
        $slot = $bundle->getPickupWindow();
        ?>
        <label for="pickup-window">Pickup Window</label>
        <select class="dropdown" name="pickup-window" id="pickup-window">
            <option value="00:00-01:00" <?php echo $slot == '00:00-01:00' ? 'selected' : ''; ?>>00:00-01:00</option>
            <option value="01:00-02:00" <?php echo $slot == '01:00-02:00' ? 'selected' : ''; ?>>01:00-02:00</option>
            <option value="02:00-03:00" <?php echo $slot == '02:00-03:00' ? 'selected' : ''; ?>>02:00-03:00</option>
            <option value="03:00-04:00" <?php echo $slot == '03:00-04:00' ? 'selected' : ''; ?>>03:00-04:00</option>
            <option value="04:00-05:00" <?php echo $slot == '04:00-05:00' ? 'selected' : ''; ?>>04:00-05:00</option>
            <option value="05:00-06:00" <?php echo $slot == '05:00-06:00' ? 'selected' : ''; ?>>05:00-06:00</option>
            <option value="06:00-07:00" <?php echo $slot == '06:00-07:00' ? 'selected' : ''; ?>>06:00-07:00</option>
            <option value="07:00-08:00" <?php echo $slot == '07:00-08:00' ? 'selected' : ''; ?>>07:00-08:00</option>
            <option value="08:00-09:00" <?php echo $slot == '08:00-09:00' ? 'selected' : ''; ?>>08:00-09:00</option>
            <option value="09:00-10:00" <?php echo $slot == '09:00-10:00' ? 'selected' : ''; ?>>09:00-10:00</option>
            <option value="10:00-11:00" <?php echo $slot == '10:00-11:00' ? 'selected' : ''; ?>>10:00-11:00</option>
            <option value="11:00-12:00" <?php echo $slot == '11:00-12:00' ? 'selected' : ''; ?>>11:00-12:00</option>
            <option value="12:00-13:00" <?php echo $slot == '12:00-13:00' ? 'selected' : ''; ?>>12:00-13:00</option>
            <option value="13:00-14:00" <?php echo $slot == '13:00-14:00' ? 'selected' : ''; ?>>13:00-14:00</option>
            <option value="14:00-15:00" <?php echo $slot == '14:00-15:00' ? 'selected' : ''; ?>>14:00-15:00</option>
            <option value="15:00-16:00" <?php echo $slot == '15:00-16:00' ? 'selected' : ''; ?>>15:00-16:00</option>
            <option value="16:00-17:00" <?php echo $slot == '16:00-17:00' ? 'selected' : ''; ?>>16:00-17:00</option>
            <option value="17:00-18:00" <?php echo $slot == '17:00-18:00' ? 'selected' : ''; ?>>17:00-18:00</option>
            <option value="18:00-19:00" <?php echo $slot == '18:00-19:00' ? 'selected' : ''; ?>>18:00-19:00</option>
            <option value="19:00-20:00" <?php echo $slot == '19:00-20:00' ? 'selected' : ''; ?>>19:00-20:00</option>
            <option value="20:00-21:00" <?php echo $slot == '20:00-21:00' ? 'selected' : ''; ?>>20:00-21:00</option>
            <option value="21:00-22:00" <?php echo $slot == '21:00-22:00' ? 'selected' : ''; ?>>21:00-22:00</option>
            <option value="22:00-23:00" <?php echo $slot == '22:00-23:00' ? 'selected' : ''; ?>>22:00-23:00</option>
            <option value="23:00-00:00" <?php echo $slot == '23:00-00:00' ? 'selected' : ''; ?>>23:00-24:00</option>
        </select>
    </div>

    <br>

    <div class="edit-form__field">
        <label for="category-selector">Category</label>
        <select class="category-selector <?php echo ($myCategory == null) ? "disabled": "" ?>" id="category-selector">
            <?php
            echo "<option value=\"\" disabled " . (($myCategory == null) ? "selected" : "") . ">Choose a category</option>";
            foreach ($categoryList as $key => $category) {
                echo "<option value=\"" . $category . "\"" . (($myCategory == $category) ? "selected" : "") . ">" . $category . "</option>";
            }
            ?>
        </select>
    </div>

    <br>
    <button type="button" class="button round red" id="add-allergen-btn">Add Allergen</button>
    <br>
    <ul class="allergen-list">
        <?php
        foreach ($bundle->getAllergens() as $allergen) {
            ?>
            <li class="allergen-list__item">
                <select class="allergen-list__item__selector">
                    <option value="" disabled="">Choose an allergen</option>
                    <option value="celery" <?php echo $allergen == "celery" ? 'selected=""' : ''; ?>>Celery</option>
                    <option value="gluten" <?php echo $allergen == "gluten" ? 'selected=""' : ''; ?>>Gluten</option>
                    <option value="crustaceans" <?php echo $allergen == "crustaceans" ? 'selected=""' : ''; ?>>Crustaceans</option>
                    <option value="eggs" <?php echo $allergen == "eggs" ? 'selected=""' : ''; ?>>Eggs</option>
                    <option value="fish" <?php echo $allergen == "fish" ? 'selected=""' : ''; ?>>Fish</option>
                    <option value="lupin" <?php echo $allergen == "lupin" ? 'selected=""' : ''; ?>>Lupin</option>
                    <option value="milk" <?php echo $allergen == "milk" ? 'selected=""' : ''; ?>>Milk</option>
                    <option value="molluscs" <?php echo $allergen == "molluscs" ? 'selected=""' : ''; ?>>Molluscs</option>
                    <option value="mustard" <?php echo $allergen == "mustard" ? 'selected=""' : ''; ?>>Mustard</option>
                    <option value="nuts" <?php echo $allergen == "nuts" ? 'selected=""' : ''; ?>>Nuts</option>
                    <option value="peanuts" <?php echo $allergen == "peanuts" ? 'selected=""' : ''; ?>>Peanuts</option>
                    <option value="sesame-seeds" <?php echo $allergen == "sesame-seeds" ? 'selected=""' : ''; ?>>Sesame Seeds</option>
                    <option value="soya" <?php echo $allergen == "soya" ? 'selected=""' : ''; ?>>Soya</option>
                    <option value="sulphites" <?php echo $allergen == "sulphites" ? 'selected=""' : ''; ?>>Sulphur dioxide/sulphites</option>
                </select>
                <button type="button" class="allergen-list__item__delete-btn"><img src="/assets/icons/bin_faded.png" width="20px" height="20px"></button>
            </li>

            <?php
        }
        ?>
    </ul>
    <br>
    <div class="edit-form__field">
        <label for="quantity">Quantity</label>
        <div class="textbox" data-type="text" data-id="quantity" data-label="Quantity" id="quantity-textbox" data-value="<?php
        print($quantity);
        ?>"></div>
    </div>
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

<script src="/assets/js/bundle_form.js"></script>
<script src="/assets/js/edit.js"></script>


<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

