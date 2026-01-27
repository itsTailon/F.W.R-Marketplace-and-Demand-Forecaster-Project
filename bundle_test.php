<?php
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Bundle Test";

// Include page head
require_once 'partials/head.php';
?>

<?php

echo '<pre>';
print_r(\TTE\App\Model\Bundle::load(4));
echo '</pre>';
?>

<!-- Markup goes here -->

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

