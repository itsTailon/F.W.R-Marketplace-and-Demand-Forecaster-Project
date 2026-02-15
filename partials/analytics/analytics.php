<?php


use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Account;
use TTE\App\Model\Customer;
use TTE\App\Model\Bundle;



$user = Authenticator::getCurrentUser();
$userID = $user->getUserID();

$bundles = Seller::getAllBundlesForUser($userID);

$collected = 0;
$noshow = 0;
$expired = 0;
foreach ($bundles as $b) {
    switch ($b['bundleStatus']) {
        case 'collected':
            $collected++;
            break;
        case 'expired':
            $expired++;
            break;
        default:
            break;
    }
}

// price effectivness stuff


$priceEfCounts = [];

foreach ($bundles as $b) {
    if($b['bundleStatus'] != 'collected'){
      continue;
    }
    $price = round(100*((float)$b['rrp'] - (float)$b['discountedPrice']));
    
    if (!isset($priceEfCounts[$price])) {
        $priceEfCounts[$price] = 0;
    }
    $priceEfCounts[$price]++;
}

ksort($priceEfCounts);

$labels = [];
foreach (array_keys($priceEfCounts) as $price) {
    $labels[] = '£' . number_format($price / 100, 2);
}
$data   = array_values($priceEfCounts);
$labelsJson = json_encode($labels);
$dataJson   = json_encode($data);

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
    <h3 class="analytics-subtitle">Sell Through</h3>

    <canvas id="analytics-salespiechart" class="analytics-salespiechart"></canvas>

    <br>
    <br>
    
    <h3 class="analytics-subtitle">Price Effectiveness</h3>
    <canvas id="analytics-sellthrough" class="analytics-sellthrough"></canvas>
    <br>
    <br>

</div>









<script src="assets/js/lib/Chart/chart.umd.min.js"></script>
<script>
const labels = ["No-Show", "Collected", "Expired"];
const values = [0, <?php echo $collected?>, <?php echo $expired ?>];

new Chart(document.getElementById('analytics-salespiechart'), {
  type: 'pie',
  data: {
    labels: labels,
    datasets: [{
      data: values,
      backgroundColor: ['#ff6565', '#4aff62', '#4b90ff']
    }]
  },
  options: {
    responsive: false,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: { enabled: true }
    }
  }
});


const labels2 = <?php echo $labelsJson ?>;
const data2 = {
  labels: labels2,
  datasets: [
    {
      label: 'Dataset 1',
      data: <?php echo $dataJson ?>,
      borderColor: '#ff6565',
      backgroundColor: 'rgba(255, 101, 101, 0.35)',
    },
  ]
};
new Chart(document.getElementById('analytics-sellthrough'), {
    type: 'line',
  data: data2,
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: false,
      },
    },
    scales: {
      x: {
        title: {
          font: {
            size: 24,
            weight: 'bold'
          },
          display: true,
          text: 'Discount'
        }
      },
      y: {
        title: {
          font: {
            size: 24,
            weight: 'bold'
          },
          size: 18,
          display: true,
          text: 'Sales Volume'
        },
        beginAtZero: true
      }
    }
  },
});
</script>



