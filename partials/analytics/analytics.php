



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
            <a class="button button--rounded">
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

</div>







<script src="assets/js/lib/Chart/chart.umd.min.js"></script>
<script>
const labels = ["No-Show", "Collected", "Expired"];
const values = [10, 5, 2];

new Chart(document.getElementById('analytics-salespiechart'), {
    type: 'pie',
    data: {
    labels,
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
</script>


