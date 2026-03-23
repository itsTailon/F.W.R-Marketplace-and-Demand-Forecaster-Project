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

if (Authenticator::getCurrentUser()->getAccountType() !== "maintainer") {
    header("Location: dashboard.php");
    die("You do not have permission to access this page.");
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

// Construct comma-separated list of existing categories;
$categories = "";
foreach (Category::getCategoryList() as $category) {
    $categories .= $category . ',';
}
$categories = rtrim($categories, ',');

?>

<div class="manage-categories-container">
    <div class="manage-categories">
        <h1>Manage Categories</h1>
        <span class="manage-categories__desc">To define available categories, please enter category names separated by commas (e.g., 'Cakes,Sandwiches'). </span>

        <div class="categories-input">
            <div class="textbox textbox--size-fill" data-type="text" data-label="Category List" data-id="categories" data-value="<?php echo $categories; ?>" id="categories-textbox"></div>
        </div>

        <button id="submit" class="button manage-categories__submit">Save Changes</button>
    </div>
</div>

<script>
    $("#submit").click(function() {
        // Get categories as an array
        var categories = $("#categories").val().split(",");

        // Ensure that at least one category is present
        if (categories.length == 1 && categories[0] === "") {
            alert("Please submit at least one category.")
            return;
        }

        // Strip leading and trailing whitespace from each category
        for (var i = 0; i < categories.length; i++) {
            categories[i] = categories[i].trim();
        }

        // Send API request
        $.ajax({
            url: "/backend/API/Model/category.php",
            type: "POST",
            data: {
                categories: JSON.stringify(categories),
            },

            statusCode: {
                200: () => { // Success
                    alert("Successfully updated system-wide categories.");
                    location.reload();
                },

                400: () => {
                    alert("Could not update system-wide categories.");
                    location.reload();
                },

                500: () => {
                    alert("Could not update system-wide categories. Did you try to remove a category still in use?");
                    location.reload();
                }
            }
        });
    });
</script>

<?php
require_once 'partials/footer.php';
?>

