<?php
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Page Not Found";

// Include page head
require_once 'partials/head.php';
?>

<div class="http404-wrapper">
    <div class="http404">
        <h1 class="http404__heading">Oops!</h1>
        <span class="http404__subheading">The page you're looking for could not be found.</span>

        <a class="button" href="/dashboard.php">Go to dashboard</a>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

