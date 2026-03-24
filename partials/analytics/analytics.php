<?php


use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Account;
use TTE\App\Model\Reservation;
use TTE\App\Model\Customer;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;



$user = Authenticator::getCurrentUser();
$userID = $user->getUserID();

$bundles = Seller::getAllBundlesForUser($userID);

$seller = Seller::load($userID);

// Get quick stats


$all_reservations = Reservation::getAllReservationsForUser($userID, "seller");

$revenue = 0;
$numberOfSales = 0;
$numberReserved = 0;
$numberOfReservations = 0;

$pricingEffectivenessData = [0,0,0,0,0,0,0,0,0,0];


// pickup window stuff
$pickupTimeData = [];

//categoryData 
$categoryData = [];



// [Collected, No-Show, Expired]
$sellThroughData = [0,0,0]; // to be added at a later date.


foreach($all_reservations as $r) {
    $numberOfReservations += 1;

    if($r['reservationStatus'] == 'completed') {
        $revenue += $r['discountedPrice'];
        $numberOfSales += 1;

        // increment sellThroughData collected
        $sellThroughData[0] += 1;

        // pricingEffectiveness stuff
        //get discount percentage
        $discountPercentage = 1 - ($r['discountedPrice'] / $r['rrp']);
        $pricingEffectivenessData[floor($discountPercentage * 10)] += 1;

        // pickup window stuff
        if(isset($pickupTimeData[$r['pickupWindow']])) {
            $pickupTimeData[$r['pickupWindow']] += 1;
        }
        else {
            $pickupTimeData[$r['pickupWindow']] = 1;
        }

        
        // get bundle linked to reservation
        try {
            $rBundle = Bundle::load($r['bundleID']);
            if(isset($categoryData[$rBundle->getCategory()])) {
                $categoryData[$rBundle->getCategory()] += 1;
            }
            else {
                $categoryData[$rBundle->getCategory()] = 1;
            }
        } catch (Exception $e){

        }



    }

    else if($r['reservationStatus'] == 'active'){
        $numberReserved += 1;
    }
    else if($r['reservationStatus'] == 'no-show') {
        $sellThroughData[1] += 1;   
    }
}


$bundlesExpired = Bundle::loadAllExpiredBundles();
foreach($bundlesExpired as $eb) {
    $sellThroughData[2] += $eb->getQuantity();
} 


$collectionRate = $numberOfReservations > 0 ? round(($numberOfSales / $numberOfReservations) * 100, 2) : 0;


// get top pickup time

$topPickupTime = 'None';
if(count($pickupTimeData) > 0) {
    $keys = array_keys($pickupTimeData);
    $values = array_values($pickupTimeData);
    $topIndex = 0;
    $topIndexVal = 0;
    for($i = 0; $i < count($values); $i++) {
        if($values[$i] > $topIndexVal) {
            $topIndex = $i;
            $topIndexVal = $values[$i];
        }
    }
    $topPickupTime = $keys[$topIndex];
}

$topCategory = 'None';
if(count($categoryData) > 0) {
    $keys = array_keys($categoryData);
    $values = array_values($categoryData);
    $topIndex = 0;
    $topIndexVal = 0;
    for($i = 0; $i < count($values); $i++) {
        if($values[$i] > $topIndexVal) {
            $topIndex = $i;
            $topIndexVal = $values[$i];
        }
    }
    $topCategory = $keys[$topIndex];
}


function cmp($a, $b) {
    $valA = intval(explode(':', $a)[0]);
    $valB = intval(explode(':', $b)[0]);

    if($valA == $valB) {
        return 0;
    }
    
    return ($valA < $valB) ? -1 : 1;
} 

uksort($pickupTimeData, 'cmp');

// --------------

// price effectiveness data







?>



<nav class="analytics-nav">
    <ul class="analytics-nav-left">
        <li>
            <a class="button button--rounded" href="/dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                <span>Home</span>
            </a>
        </li>
    </ul>
    <ul class="analytics-nav-right">
        <li>
            <a href="/forecast.php" class="button button--rounded">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M320-414v-306h120v306l-60-56-60 56Zm200 60v-526h120v406L520-354ZM120-216v-344h120v224L120-216Zm0 98 258-258 142 122 224-224h-64v-80h200v200h-80v-64L524-146 382-268 232-118H120Z"/></svg>
                <span>Forecast</span>
            </a>
        </li>
    </ul>
</nav>


<div class="analytics-info">
    <h1 class="analytics-title">Analytics</h1>
    <p class="analytics-subtitle">Your stores analytics</p>
    <div class="analytics-quick-stats">
      <div class="analytics-quick-stats-bubble">
          <span class="analytics-quick-stats-bubble-type">REVENUE</span>
          <span class="analytics-quick-stats-bubble-value">£<?php echo htmlspecialchars($revenue); ?></span>
      </div>
      <div class="analytics-quick-stats-bubble">
          <span class="analytics-quick-stats-bubble-type">COLLECTION RATE</span>
          <span class="analytics-quick-stats-bubble-value"><?php echo htmlspecialchars($collectionRate); ?>%</span>
      </div>
      <div class="analytics-quick-stats-bubble">
          <span class="analytics-quick-stats-bubble-type">SALES</span>
          <span class="analytics-quick-stats-bubble-value"><?php echo htmlspecialchars($numberOfSales); ?></span>
      </div>
      <div class="analytics-quick-stats-bubble">
          <span class="analytics-quick-stats-bubble-type">RESERVATIONS</span>
          <span class="analytics-quick-stats-bubble-value"><?php echo htmlspecialchars($numberReserved); ?></span>
      </div>

    </div>

    <div class="analytics-graphs">
        <div class="analytics-graphs-pricing-effectiveness-bubble">
            <span class="analytics-graphs-pricing-effectiveness-bubble-title">PRICE EFFECTIVENESS</span>
            <div>
                <canvas id="pricingEffectivenessChart"></canvas>
            </div>
        </div>
        <div class="analytics-graphs-sell-through-bubble">
            <span class="analytics-graphs-pricing-effectiveness-bubble-title">SELL THROUGH</span>
            <div>
                <canvas id="sellThroughChart"></canvas>
            </div>
            
        </div>
    </div>

    <div class="analytics-more-stats">
        <div class="analytics-more-stats-bubble">
            <span class="analytics-more-stats-bubble-type">ESTIMATED WASTE AVOIDED</span>
            <span class="analytics-more-stats-bubble-value"><?php echo htmlspecialchars($numberOfSales*1.3); ?>kg</span>
        </div>
        <div class="analytics-more-stats-bubble">
            <span class="analytics-more-stats-bubble-type">TOP CATEGORY</span>
            <span class="analytics-more-stats-bubble-value"><?php echo htmlspecialchars($topCategory); ?></span>
        </div>
        <div class="analytics-more-stats-bubble">
            <span class="analytics-more-stats-bubble-type">TOP PICKUP TIME</span>
            <span class="analytics-more-stats-bubble-value"><?php echo htmlspecialchars($topPickupTime); ?></span>
        </div>
    </div>

    <div class="analytics-graphs-2">
        <div class="analytics-graphs-2-bubble">
            <span class="analytics-graphs-2-bubble-type">TOP PICKUP TIMES</span>
            <div class="analytics-graphs-2-bubble-graph">
                <canvas id="topPickupTimesChart"></canvas>
            </div>
        </div>
        <div class="analytics-graphs-2-bubble">
            <span class="analytics-graphs-2-bubble-type">TOP CATEGORIES</span>
            <div class="analytics-graphs-2-bubble-graph">
                <canvas id="topCategoriesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="analytics-seller-actions">
        <nav class="analytics-seller-actions-nav">
            <ul>
               <li class="analytics-seller-actions-nav-left">
                    <span class="analytics-seller-actions-title">SELLER ACTIONS</span>
               </li>
               <li class="analytics-seller-actions-nav-right">
                    <button class="analytics-seller-actions-create-button" id="openCreateActionButton">Create</button>
                    <button class="analytics-seller-actions-delete-button">Delete</button>
               </li> 
            </ul>
        </nav>

        <div class="analytics-seller-actions-wrapper">
            <table class="analytics-seller-actions-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ACTION</th>
                        <th>REASON</th>
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody id="sellerActionsList">
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="analytics-create-action-wrapper" hidden>
    <div class="analytics-create-action">
        <span class="analytics-create-action-title">Record Action</span>
        <div class="analytics-create-action-inputs">
            <ul>
                <li>
                    <span>Action Taken</span>
                    <input type="text" id="actionTakenField">
                </li>
                <li>
                    <span>Reasoning</span>
                    <textarea class="analytics-create-actions-inputs-reason" placeholder="Reasoning for taking Action." id="reasoningField"></textarea>
                </li>
            </ul>
        </div>

        <ul class="analytics-create-action-buttons">
            <li>
                <button class="analytics-create-action-buttons-create">Create</button>
                <button class="analytics-create-action-buttons-cancel">Cancel</button>
            </li>
        </ul>
    </div>
</div>

<div class="analytics-create-action-overlay" id="createActionOverlay" hidden></div>




<script>
    //seller actions stuff
    function loadActions() {
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = false;

        $.ajax({
            type: 'GET',
            url: '/backend/API/Model/sellerAction.php',
        success: function(resp) {
            const actionList = document.getElementById('sellerActionsList');
            actionList.innerHTML = '';

            resp.forEach(function(action) {
                const tr = document.createElement('tr');
                tr.className = 'notification-dropdown__item';
                tr.dataset.id = action.actionID;
                tr.innerHTML = `
                    <td><input type="checkbox" class="action-checkbox" data-id="${action.actionID}"></td>
                    <td>${action.action}</td>
                    <td>${action.reason}</td>
                    <td>${getTimeSince(action.createdAt)}</td>
                `;
                actionList.appendChild(tr);
            });

        },
        });


    }

    loadActions();



    document.getElementById('openCreateActionButton').addEventListener('click', () => {
        const createMenu = document.querySelector('.analytics-create-action-wrapper');

        if(createMenu.hidden === true) {
            const overlay = document.getElementById('createActionOverlay');
            overlay.hidden = false;
            createMenu.hidden = false;
        }
    });

    document.getElementById('createActionOverlay').addEventListener('click', () => {
        const overlay = document.getElementById('createActionOverlay');

        if(!overlay.hidden) {
            overlay.hidden = true;
            const createMenu = document.querySelector('.analytics-create-action-wrapper');
            createMenu.hidden = true;
        }
    });

    document.querySelector('.analytics-create-action-buttons-cancel').addEventListener('click', () => {
        const overlay = document.getElementById('createActionOverlay');
        const createMenu = document.querySelector('.analytics-create-action-wrapper');
        createMenu.hidden = true;
        overlay.hidden = true;
    });





    // form submit for the create action


    document.querySelector('.analytics-create-action-buttons-create').addEventListener('click', () => {
        const actionField = document.getElementById('actionTakenField');
        const reasonField =document.getElementById('reasoningField');

        if(actionField.value === '' || reasonField.value === '') {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '/backend/API/Model/sellerAction.php',
            data: {
                action: actionField.value,
                reason:reasonField.value,
            },
        success: function() {
            const createMenu = document.querySelector('.analytics-create-action-wrapper');
            const overlay = document.getElementById('createActionOverlay');

            createMenu.hidden = true;
            overlay.hidden = true;


            actionField.value = '';
            reasonField.value = '';

            loadActions();

        }
        });
    });



    // add check box functionality for list

    document.querySelector('.analytics-seller-actions-table').addEventListener('change', function(e) {
        if(e.target.id === 'selectAll') {
            // select all logic
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"');
            let isChecked = e.target.checked;
            allCheckboxes.forEach(function(cb) {
                cb.checked = isChecked;
            });

        }
        else {
            // normal checkbox logic.
            // check if should change select all button 
            const allCheckboxes = document.querySelectorAll('.action-checkbox');


            let update = true;
            for(let cb of allCheckboxes) {
                if(cb.checked === false) {
                    console.log(cb.checked);
                    update = false;
                }
            }
            const selectAllCheckbox = document.getElementById('selectAll');
            if(update) {
                    selectAllCheckbox.checked = true;
            }
            else {
                selectAllCheckbox.checked = false;
            }
            

        }
    });




    // add delete functionality.

    document.querySelector('.analytics-seller-actions-delete-button').addEventListener('click', () => {
        const allCheckboxes = document.querySelectorAll('.action-checkbox');
        ids = [];

        for(let cb of allCheckboxes) {
            if(cb.checked) {
                ids.push(cb.dataset.id);
            }
        }

        for(let id of ids) {
            $.ajax({
                type: 'DELETE',
                url: '/backend/API/Model/sellerAction.php',
                data: {
                    actionID: id,
                },
            success: function() {
                loadActions();
            },
            error: function() {
                console.log("error deleting action");
            }
            });
        }


    });


</script>





<script src="assets/js/lib/Chart/chart.umd.min.js"></script>
<script>
    // chart stuff

    const data = <?php echo json_encode($pricingEffectivenessData); ?>;
    const labels = ['0-10%','10-20%', '20-30%', '30-40%', '40-50%', '50-60%', '60-70%', '70-80%','80-90%', '90-100%'];

    new Chart(document.getElementById('pricingEffectivenessChart'), {
        type: 'bar',
        options: {
            animation: true,
            
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true
                },
            },  
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Discount Level',
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Number of Sales',
                    }
                }
            }
        },
        data: {
            labels: labels,
            datasets: [{
                data: data,
                barThickness: 35,
                backgroundColor: 'rgb(208, 220, 255)',
                borderRadius: 5,

            }]
        }
    


    });

    // Sell through

    const sellData = <?php echo json_encode($sellThroughData); ?>;;
    const sellLabels = ['Collected', 'No-Show', 'Expired'];
    new Chart(document.getElementById('sellThroughChart'), {
        type: 'doughnut',
        options: {
            cutout: 80,
            animation: true,
            
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                },
                tooltip: {
                    enabled: true
                }
            }
        },
        data: {
            labels: sellLabels,
            datasets: [{
                data: sellData,
                backgroundColor: [
                    'rgb(208, 220, 255)',
                    'rgb(27, 27, 27)',
                    'rgb(255, 105, 105)'
                ]
            }]
        }
    });

    // pickupWindow

    const pickupTimeLabels = <?php echo json_encode(array_keys($pickupTimeData)); ?>;
    const pickupTimeValues = <?php echo json_encode(array_values($pickupTimeData)); ?>;

    if (pickupTimeLabels.length > 0) {
        new Chart(document.getElementById('topPickupTimesChart'), {
            type: 'bar',
            options: {
                animation: true,
                plugins: {
                    legend: {
                         display: false 
                    },
                    tooltip: { 
                        enabled: true 
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Pickup Time',
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Collections',
                        }
                    }
                }
            },
            data: {
                labels: pickupTimeLabels,
                datasets: [{
                    data: pickupTimeValues,
                    barThickness: 35,
                    backgroundColor: 'rgb(73, 73, 73)',
                    borderRadius: 5,
                }]
            }
        });
    } else {
        document.getElementById('topPickupTimesChart').parentElement.innerHTML = '<p>No pickup data available yet.</p>';
    }



    //category stuff
    const categoryLabels = <?php echo json_encode(array_keys($categoryData)); ?>;
    const categoryValues = <?php echo json_encode(array_values($categoryData)); ?>;

    if (categoryLabels.length > 0) {
        new Chart(document.getElementById('topCategoriesChart'), {
            type: 'bar',
            options: {
                animation: true,
                
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Category',
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number Of Sales',
                        }
                    }
                }
            },
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    barThickness: 35,
                    backgroundColor: 'rgb(255, 125, 125)',
                    borderRadius: 5,

                }]
            }


        });
    }
    else {
        document.getElementById('topCategoriesChart').parentElement.innerHTML = '<p>No category data available yet.</p>';
    }
    
</script>



