<?php
require 'vendor/autoload.php';

session_start();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($DOCUMENT_TITLE) ? $DOCUMENT_TITLE . ' — ' : '';?>App Name</title>

    <!-- Fonts  -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>
</head>
<body>

