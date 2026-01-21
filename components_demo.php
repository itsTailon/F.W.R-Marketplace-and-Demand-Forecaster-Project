<!--

This file is independent of the project app, and is used to show how to use various custom components designed
for use throughout development.

-->

<!doctype html>
<html lang="en">
<head>
    <!-- Specify character set  -->
    <meta charset="UTF-8">

    <!-- Responsive best practice (see what happens on a larger, fuller page when this is removed on mobile.  -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--  Page title, as shown in tabs and as would be indexed by search engines  -->
    <title>Components Demo</title>

    <!--  Load the main project stylesheet  -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!--

    Load JQuery, a library which speeds up JS development by providing a simpler API for things such as DOM
    manipulation and AJAX.

    This is loaded in the head tag, as other scripts will be dependent on JQuery, and thus it must be
    loaded first.

    -->
    <script src="/assets/js/lib/jquery/jquery-4.0.0.min.js"></script>

    <!--
    Never write CSS in a style tag, and never use inline CSS.

    This is here strictly for the demo, but please note that this is not best practice for general development.
    -->
    <style>
        .demo-wrapper {
            width: 100vw;
            min-height: 100vh;
            padding: 128px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
    </style>
</head>
<body>
    <div class="demo-wrapper">

        <!--

        To create a textbox, create a div with the class 'textbox' as follows. JavaScript logic will populate the
        div automatically with all subcomponents forming the text input.

        Use the custom data attributes shown to indicate what the resultant (actually rendered) tags'
        attributes/contents will be set to:
            - data-type:    the input 'type' attribute value (e.g., 'email', 'password', 'text')
            - data-label:   the contents of the rendered label tag
            - data-id:      the input 'id' attribute value (e.g., 'First Name', 'E-mail Address')

        -->
        <div class="textbox textbox--size-fill" data-type="text" data-label="Basic Input" data-id="sample-textbox"></div>


        <!--

        To put a textbox in an 'error' state, use the 'textbox--error' class.

        ** Don't forget to use the base 'textbox' class, too! **

        -->
        <div class="textbox textbox--error" data-type="text" data-label="Error Input" data-id="sample-error-textbox"></div>


        <!--

        Use the 'button' class for a button (can be used on input:submit tags and anchor tags, too).

        -->
        <button class="button">Click me</button>


        <!--

        Combine the 'button' class with 'button--text-left-align' for left-aligned button text.

        -->
        <button class="button button--text-align-left">Click me (but left-aligned)</button>

    </div>
</body>

<!--

Include script for rendering text inputs.

This is placed at the end of the file, as one should generally place DOM-maniuplating scripts after
elements have been defined.

-->
<script src="/assets/js/components/text-inputs.js"></script>

</html>