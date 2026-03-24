<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Create Seller";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

$acc = Authenticator::getCurrentUserSubclass();


?>



<div class="create-seller-wrapper">
    <div class="create-seller">

        <div class="create-seller-nav">
            <ul>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </ul>
        </div>
        <form class="create-seller-form" id="create-seller-form" method="POST" action="/backend/API/Model/seller.php">
            <h1 class="create-seller-form-title">Create Seller</h1>
            <h3 id="status-message"></h3>
            <div class="create-seller-form-input-section">
                <label>Name</label>
                <input id="name" type="text" placeholder="Name" name="name">
            </div>
            <div class="create-seller-form-input-section">
                <label>Email</label>
                <input id="email" type="email" placeholder="Email" name="email">
            </div>
            <div class="create-seller-form-input-section">
                <label>Password</label>
                <input id="password" type="password" placeholder="Password" name="password">
            </div>
            <div class="create-seller-form-input-section">
                <label>Address</label>
                <input id="address" type="text" placeholder="Address" name="address">
            </div>
            <div class="create-seller-form-buttons">
                <button type="submit" class="create-seller-form-buttons-submit">Submit</button>
                <button type="button" class="create-seller-form-buttons-clear">Clear</button>
            </div>
        </form>
        
    </div>
</div>
<script src="/assets/js/components/validation.js"></script>


<script>

const statusMessage = document.getElementById('status-message');
$('.create-seller-form').on('submit', function (e) {
    e.preventDefault();


    const email = $('#email').val();
    const password = $('#password').val();

    if(!validateEmail(email)) {
        statusMessage.className = 'error';
        statusMessage.textContent = 'Invalid email!';
        return;
    }

    const passwordResult = validatePassword(password);
    if(passwordResult !== 'PASS') {
        statusMessage.className = 'error';
        statusMessage.textContent = passwordResult;
        return;
    }

    $.ajax({
        type: 'POST',
        url: '/backend/API/Model/seller.php',
        data: $(this).serialize(),
    success: function () {
        statusMessage.className = 'success';
        statusMessage.textContent = 'Seller account created!';
        document.getElementById("create-seller-form").reset();

        

    },
    error: function (err) {
        statusMessage.className = 'error';
        statusMessage.textContent ='Failed: something went wrong.';
    }
    });
});

$('.create-seller-form-buttons-clear').on('click', function () {
    document.getElementById("create-seller-form").reset(); 
    statusMessage.className = '';
})
$('.create-seller-form input').on('input', function () {
    statusMessage.className = '';
    statusMessage.textContent = '';
});
</script>



<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

