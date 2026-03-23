<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "View Seller";

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

// No ID passed, so redirect to 404
if (!isset($_GET['id'])) {
    header('Location: /404.php');
    die();
}


// Check that int was passed as ID
$sellerID = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!is_int($sellerID)) {
    header('Location: /404.php');
    die();
}


// Ensure that ID corresponds to a seller
if (!Seller::existsWithID($sellerID)) {
    header('Location: /404.php');
    die();
}

$seller = Seller::load($sellerID);





?>



<div class="view-seller-wrapper">
    <div class="view-seller">

        <div class="view-seller-nav">
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
        <form class="view-seller-form" id="view-seller-form">
            <h1 class="view-seller-form-title">View Seller</h1>
            <h3 id="status-message"></h3>
            <div class="view-seller-form-input-section">
                <ul>
                    <li class="view-seller-form-input-section-view">
                        <label class="view-seller-form-type">Name</label>
                        <label class="view-seller-form-val"><?= htmlspecialchars($seller->getName()) ?></label>
                        <button class="view-seller-form-edit">edit</button>
                    </li>
                    <li class="view-seller-form-input-section-edit">
                        <input data-edit-toggle="hide" id="name" type="text" placeholder="New Name" name="name" hidden>
                        <button data-edit-toggle="hide" type="button" class="change-btn" hidden >Change</button>
                    </li>
                </ul>    
            </div>
            <div class="view-seller-form-input-section">
                <ul>
                    <li class="view-seller-form-input-section-view">
                        <label class="view-seller-form-type">Email</label>
                        <label class="view-seller-form-val"><?= htmlspecialchars($seller->getEmail()) ?></label>
                        <button class="view-seller-form-edit">edit</button>
                    </li>
                    <li class="view-seller-form-input-section-edit">
                        <input data-edit-toggle="hide" id="email" type="email" placeholder="Email" name="email" hidden>
                        <button data-edit-toggle="hide" type="button" class="change-btn" hidden>Change</button>
                    </li>
                </ul>
            </div>
            <div class="view-seller-form-input-section">
                <ul>
                    <li class="view-seller-form-input-section-view">
                        <label class="view-seller-form-type">Password</label>
                        <label class="view-seller-form-val">*********</label>
                        <button class="view-seller-form-edit">edit</button>
                    </li>
                    <li class="view-seller-form-input-section-edit">
                        <input data-edit-toggle="hide" id="password" type="password" placeholder="Password" name="password" hidden>
                        <button data-edit-toggle="hide" type="button" class="change-btn" hidden>Change</button>
                    </li>
                </ul>
            </div>
            <div class="view-seller-form-input-section">
                <ul>
                    <li class="view-seller-form-input-section-view">
                        <label class="view-seller-form-type">Address</label>
                        <label class="view-seller-form-val"><?= htmlspecialchars($seller->getAddress()) ?></label>
                        <button class="view-seller-form-edit">edit</button>
                    </li>
                    <li class="view-seller-form-input-section-edit">
                        <input data-edit-toggle="hide" id="address" type="text" placeholder="Address" name="address" hidden>
                        <button data-edit-toggle="hide" type="button" class="change-btn" hidden>Change</button>
                    </li>
                </ul>
            </div>
            <div class="view-seller-form-buttons">
                <button type="button" class="view-seller-form-buttons-delete">Delete</button>
            </div>
        </form>
        
    </div>
</div>

<script>
const statusMessage = document.getElementById('status-message');
const sellerID = <?php echo htmlspecialchars($seller->getUserID()) ?>


// edit button toggle
document.querySelectorAll('.view-seller-form-edit').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const li = this.closest('ul').querySelector('.view-seller-form-input-section-edit');
        const inputs = li.querySelectorAll('[data-edit-toggle="hide"]');

        const isHidden = inputs[0].hidden;

        for(let i = 0; i < inputs.length; i++) {
            inputs[i].hidden = !isHidden;
        }
        this.textContent = isHidden ? 'cancel' : 'edit';
    });
});

document.querySelectorAll('.change-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const li = this.closest('ul').querySelector('.view-seller-form-input-section-edit');
        const input = li.querySelector('input');
        const inputVal = input.value;
        const inputType = input.name;
        const editButton = this.closest('ul').querySelector('.view-seller-form-input-section-view').querySelector('button');
        const viewVal = this.closest('ul').querySelector('.view-seller-form-input-section-view').querySelector('.view-seller-form-val');




        if(inputType === 'password') {
            statusMessage.className = 'error';
            statusMessage.textContent = 'Update password is not working right now.';
            return;
        }


        // get default values
        const allViews = document.querySelectorAll('.view-seller-form-input-section-view');


        let data = {['sellerID']: sellerID};
        for(let i = 0; i < allViews.length; i++) {

            let currentView = allViews[i];

            let type = currentView.querySelector('.view-seller-form-type').textContent.toLowerCase();
            let val = currentView.querySelector('.view-seller-form-val').textContent;

            if(type != 'password') {
                data[type] = val;
            }

        }




        data[inputType] = inputVal;

        if(inputType === 'email' && !validateEmail(inputVal)) {
            statusMessage.className = 'error';
            statusMessage.textContent = 'Invalid email!';
            return;
        }

        if(inputType === 'password') {
            const passwordResult = validatePassword(inputVal);
            if(passwordResult !== 'PASS') {
                statusMessage.className = 'error';
                statusMessage.textContent = passwordResult;
                return;
            }
        }

        $.ajax({
            type: 'PUT',
            url: '/backend/API/Model/seller.php',
            data: data,
        success: function () {
            console.log("here");
            if(inputType != 'password') {
                viewVal.textContent = inputVal;
            }

            statusMessage.className = 'success';
            statusMessage.textContent = 'Successfuly updated ' + inputType + ' to \'' + inputVal + '\'.';


            // now hide change field.
            editButton.click();
            input.value = '';
        },
        error: function(e) {
            statusMessage.className = 'error';
            statusMessage.textContent = 'Failed to update ' + inputType + ' field!';
            editButton.click();
        }

        });

    });
});

document.querySelector('.view-seller-form-buttons-delete').addEventListener('click', function() {
    // get default values
    if (!confirm('Are you sure you want to delete this Account?')) {
        return;
    }

    $.ajax({
        type: 'DELETE',
        url: '/backend/API/Model/seller.php',
        data: {
            ['sellerID']: <?php echo htmlspecialchars($seller->getUserID()) ?>,
        },
        success: function () {
            statusMessage.className = 'success';
            statusMessage.textContent = 'Account successfuly deleted!';
            alert('Account successfuly deleted!');
            window.location.href = '/dashboard.php';
        },
        error: function(e) {
            statusMessage.className = 'error';
            statusMessage.textContent = 'Failed to delete account: ' + e.responseText;
        }
    });
});

</script>
<script src="/assets/js/components/validation.js"></script>



<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

