<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Category;

require 'partials/head.php';

// If user is not logged in, briefly display an error
// and then redirect to login
if (!Authenticator::isLoggedIn()) {
    echo <<<XYZ
    <p>ERROR: Not logged in!</p>
    <p>If not redirected automatically, please click <a href="login.php">here</a>.</p>
    <script>
        function redirectToLogin() {
            location.href = "/login.php";
        }
    </script>
    XYZ;
    die();
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

$categoryList = Category::getCategoryList();
?>

<input type="hidden" name="sellerID" value="<?php echo Authenticator::getCurrentUser()->getUserID(); ?>">

<div class="bundle-creation-container">
    <div class="bundle-creation-wrapper">
        <div class="bundle-dashboard-buttons">
            <!-- TODO: Add symbols  -->
            <a href="" class="button button--rounded">Listings</a>
            <a href="dashboard.php" class="button button--rounded">Home</a>
        </div>

        <section class="bundle-creation-form">
            <h1>Create a Listing</h1>
            <br>
            <p class="error-text"></p>

            <div class="bundle-creation-form__field">
                <label for="name">Name</label>
                <div class="textbox" data-type="text" data-id="name" id="name-textbox"></div>
            </div>
            <br>
            <div class="bundle-creation-form__field">
                <label for="description">Description</label>
                <textarea class="textarea" id="description"></textarea>
            </div>
            <br>
            <div class="bundle-creation-form__field">
                <label for="category-selector">Category</label>
                <select class="category-selector disabled" id="category-selector">
                    <option value="" disabled selected>Choose a category</option>
                    <?php
                        foreach ($categoryList as $key => $category) {
                            echo "<option value=\"" . $category . "\">" . $category . "</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="bundle-creation-form__field">
                <label for="pickup-window">Pickup Window</label>
                <select class="dropdown" name="pickup-window" id="pickup-window">
                    <option value="00:00-01:00">00:00-01:00</option>
                    <option value="01:00-02:00">01:00-02:00</option>
                    <option value="02:00-03:00">02:00-03:00</option>
                    <option value="03:00-04:00">03:00-04:00</option>
                    <option value="04:00-05:00">04:00-05:00</option>
                    <option value="05:00-06:00">05:00-06:00</option>
                    <option value="06:00-07:00">06:00-07:00</option>
                    <option value="07:00-08:00">07:00-08:00</option>
                    <option value="08:00-09:00">08:00-09:00</option>
                    <option value="09:00-10:00">09:00-10:00</option>
                    <option value="10:00-11:00">10:00-11:00</option>
                    <option value="11:00-12:00">11:00-12:00</option>
                    <option value="12:00-13:00">12:00-13:00</option>
                    <option value="13:00-14:00">13:00-14:00</option>
                    <option value="14:00-15:00">14:00-15:00</option>
                    <option value="15:00-16:00">15:00-16:00</option>
                    <option value="16:00-17:00">16:00-17:00</option>
                    <option value="17:00-18:00">17:00-18:00</option>
                    <option value="18:00-19:00">18:00-19:00</option>
                    <option value="19:00-20:00">19:00-20:00</option>
                    <option value="20:00-21:00">20:00-21:00</option>
                    <option value="21:00-22:00">21:00-22:00</option>
                    <option value="22:00-23:00">22:00-23:00</option>
                    <option value="23:00-00:00">23:00-24:00</option>
                </select>
            </div>

            <br>
            <button type="button" class="button round red" id="add-allergen-btn">Add Allergen</button>
            <ul class="allergen-list">
            </ul>
            <div class="bundle-creation-form__field">
                <label for="quantity">Quantity</label>
                <div class="textbox" data-type="text" data-id="quantity" data-label="Quantity" id="quantity-textbox"></div>
            </div>
            <div class="bundle-creation-form__field">
                <label for="price">Price</label>
                <div class="textbox" data-type="text" data-id="rrp" data-label="Price in £" id="price-textbox"></div>
            </div>
            <div class="bundle-creation-form__field">
                <label for="discount-price">Discounted Price</label>
                <div class="textbox" data-type="text" data-id="discount-price" data-label="Price in £" id="discount-price-textbox"></div>
            </div>
            <div class="bundle-creation-form__btns">
                <button type="button" class="button button--rounded button--green" id="submit-btn">Submit</button>
                <button type="button" class="button button--rounded " id="clear-btn">Clear</button>
                <button type="button" class="button button--rounded red" id="clear-btn">Delete</button>
            </div>
        </section>
    </div>
</div>

<script src="/assets/js/bundle_form.js"></script>
<script src="/assets/js/create.js"></script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

