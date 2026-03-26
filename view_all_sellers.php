<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

// Define document (i.e. tab) title
$DOCUMENT_TITLE = "View Sellers";

// Include page head
require_once 'partials/head.php';

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

$account = Authenticator::getCurrentUser();

// TODO: uncomment when maintainers are properly implemented
// if ($account->getAccountType() != "maintainer") {
//     http_response_code(response_code: 403);
//     die("You do not have permissions to view all sellers.");
// }

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

$allSellers = Seller::getAll();

?>

<section class="all-sellers">
    <nav class="nav">
        <ul class="nav-left">
            <li>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </nav>
    <h1 class="all-sellers__header">Sellers</h1>

    <ul class="all-sellers__list">
        <?php 
            foreach ($allSellers as $seller) {
                echo "
                    <li class=\"all-sellers__list__item\">
                        <h2 class=\"all-sellers__list__item__header\">" . $seller->getName() . "</h2>
                        <p>ID: " . $seller->getUserID() . "</p>
                        <p>Email: " . $seller->getEmail() . "</p>
                        <p>Address: " . $seller->getAddress() . "</p>
                        <div class=\"all-sellers__list__item__buttons\">
                            <a href=\"/view_seller.php?id=" . $seller->getUserID() . "\" class=\"button round\">Edit</a>
                        </div>
                    </li>
                ";
            }
        ?>
    </ul>
</section>

<?php

// Include page footer and closing tags
require_once 'partials/footer.php';

?>