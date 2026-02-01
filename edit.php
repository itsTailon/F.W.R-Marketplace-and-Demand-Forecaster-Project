<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

require 'partials/head.php';

session_start();

// If user is not logged in, briefly display an error
// and then redirect to login
if (!Authenticator::isLoggedIn()) {
    echo <<<XYZ
    <!DOCTYE html>
    <head lang="en">
        <meta charset="utf-8">
        <title>Redirecting</title>
    </head>
    <body>
        <p>ERROR: Not logged in!</p>
        <script>
            function redirectToLogin() {
                location.href = "/login.php"
            }

            setTimeout(redirectToLogin, 3000);
    
        </script>
    </body>
    XYZ;
    die();
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<section class="edit-form">
    <h1>Editing [LISTING]</h1>
    <br>
    <div class="edit-form__field">
        <label for="name">Name</label>
        <div class="textbox" data-type="text" data-id="name" id="name-textbox"></div>
    </div>
    <br>
    <div class="edit-form__field">
        <label for="description">Description</label>
        <textarea class="textarea"></textarea>
    </div>
    <br>
    <button type="button" class="button round red" id="add-allergen-btn">Add Allergen</button>
    <br>
    <ul class="allergen-list">
    </ul>
    <br>
    <div class="edit-form__field">
        <label for="price">Price</label>
        <div class="textbox" data-type="text" data-id="price" data-label="Price in £" id="price-textbox"></div>
    </div>
    <br>
    <div class="edit-form__btns">
        <button type="button" class="button round green" id="submit-btn">Submit</button>
        <button type="button" class="button round" id="clear-btn">Clear</button>
        <button type="button" class="button round red" id="clear-btn">Delete</button>    
    </div>
</section>

<script src="/assets/js/components/text-inputs.js"></script>
<script src="/assets/js/edit.js"></script>
