// Get ID parameter from URL for submission
let params = new URLSearchParams(document.location.search);
let bundleID = params.get("id");

$('#submit-btn').click(() => {
    // Get values in input fields/textboxes
    var bundleName = $("#name").val();
    var bundleDesc = $("#description").val();
    var bundleRRP = $("#rrp").val();
    var bundleDiscountPrice = $("#discount-price").val();
    
    $('.error-text').text(""); // Reset red error text

    // Perform validation checks

    // Bundle name must be 3+ characters
    if (bundleName.length < 3) {
        $('.error-text').text("Bundle name must be 3 characters or more.") // Display error in red text
        return false; // Halt further execution
    }

    // Bundle name cannot exceed 100 characters
    if (bundleName.length > 100) {
        $('.error-text').text("Bundle name too long! Must be 100 characters or less.");
        return false;
    }

    // Bundle needs a description
    if (bundleDesc.length == 0) {
        $('.error-text').text("Bundle description may not be empty.");
        return false;
    }
    
    // Bundle RRP must be a number
    if (isNaN(parseFloat(bundleRRP))) {
        $('.error-text').text("Recommended retail price must be a number.");
        return false;
    }

    // Bundle discount price must be a number
    if (isNaN(parseFloat(bundleDiscountPrice))) {
        $('.error-text').text("Discounted price must be a number.");
        return false;
    }

    if (isNaN(bundleID)) { // If the ID is not a number
        $('.error-text').val("Bundle ID is not a number.");
        return false;
    }

    // Send PUT request to bundle API
    $.ajax({
        url: "/backend/API/Model/bundle.php",
        type: 'PUT',
        data: {
            bundleID: bundleID,
            title: bundleName,
            details: bundleDesc,
            rrp: bundleRRP,
            discountedPrice: bundleDiscountPrice,
            bundleStatus: "available"
        },
        statusCode: {
            200: () => { // Edit successful
                $('#bundle-name').text(bundleName);
            },
            400: () => {
                $('.error-text').text("Bad Request");
            },
            403: () => {
                $('.error-text').text("Permission denied");
            },
            404: () => {
                $('.error-text').text("Bundle not found");
            },
            500: () => {
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});

// If clear button is clicked
$("#clear-btn").click(() => {
    // Empty all the input boxes
    $("#name").val('');
    $("#description").val('');
    $("#rrp").val('');
    $("#discount-price").val('');
});

// If delete button is clicked
$('#delete-btn').click(() => {
    // Send DELETE request to API
    $.ajax({
        url: "/backend/API/Model/bundle.php",
        type: 'DELETE',
        data: {
            bundleID: bundleID
        },
        statusCode: {
            200: () => { // If deletion successful, go to dashboard
                location.href = "/dashboard.php";
            },
            403: () => { // Seller does not have permission to delete
                $('.error-text').text("Permission denied");
            }, 
            500: () => { // Internal Server Error
                $('.error-text').text("Internal Server Error");
            }
        }
    })
});
