<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

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
?>

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
            <div class="bundle-creation-form__field">
                <label for="name">Name</label>
                <div class="textbox" data-type="text" data-id="name" id="name-textbox"></div>
            </div>
            <div class="bundle-creation-form__field">
                <label for="description">Description</label>
                <textarea class="textarea"></textarea>
            </div>
            <button type="button" class="button round red" id="add-allergen-btn">Add Allergen</button>
            <ul class="allergen-list">
            </ul>
            <div class="bundle-creation-form__field">
                <label for="price">Price</label>
                <div class="textbox" data-type="text" data-id="price" data-label="Price in £" id="price-textbox"></div>
            </div>
            <div class="bundle-creation-form__btns">
                <button type="button" class="button button--rounded button--green" id="submit-btn">Submit</button>
                <button type="button" class="button button--rounded " id="clear-btn">Clear</button>
                <button type="button" class="button button--rounded red" id="clear-btn">Delete</button>
            </div>
        </section>
    </div>
</div>

<script src="/assets/js/components/text-inputs.js"></script>
<script src="/assets/js/bundle_form.js"></script>
<script src="/assets/js/create.js"></script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

