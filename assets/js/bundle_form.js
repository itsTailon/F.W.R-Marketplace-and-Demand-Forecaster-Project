var count = 0; // Used to determine the allergen index

// When mouse hovers over the delete button of an allergy, change its icon to the normal bin icon
$(document).on('mouseenter', '.allergen-list__item__delete-btn', function () {
    $(this).find("img").attr("src", "/assets/icons/bin.png");
});

// When mouse stops hovering over the delete button of an allergy, change its icon to the faded bin icon
$(document).on('mouseleave', '.allergen-list__item__delete-btn', function () {
    $(this).find("img").attr("src", "/assets/icons/bin_faded.png");
});

// When an allergen is changed, check if it should have the disabled class applied
$(document).on('change', '.allergen-list__item__selector', function() {
    if ($(this).val() !== "") { // If the allergen value is not the placeholder
        $(this).removeClass("disabled");  // Do not show as disabled
    } else { // If the allergen value is the placeholder
        $(this).addClass("disabled"); // Show as disabled
    }
});

// When an allergen delete button is clicked, delete the allergen from the list
$(document).on('click', '.allergen-list__item__delete-btn', function () {
    $(this).parent().remove();
});

/* addAllergen
Add a new allergen with drop-down selector and delete button to the list.
Index increments each time so each entry can be distinguished.
*/
function addAllergen() {
    $(".allergen-list").append(`
        <li class="allergen-list__item" index="${count}">
            <select class="allergen-list__item__selector disabled">
                <option value="" disabled selected>Choose an allergen</option>
                <option value="celery">Celery</option>
                <option value="gluten">Gluten</option>
                <option value="crustaceans">Crustaceans</option>
                <option value="eggs">Eggs</option>
                <option value="fish">Fish</option>
                <option value="lupin">Lupin</option>
                <option value="milk">Milk</option>
                <option value="molluscs">Molluscs</option>
                <option value="mustard">Mustard</option>
                <option value="nuts">Nuts</option>
                <option value="peanuts">Peanuts</option>
                <option value="sesame-seeds">Sesame Seeds</option>
                <option value="soya">Soya</option>
                <option value="sulphites">Sulphur dioxide/sulphites</option>
            </select>
            <button type="button" class="allergen-list__item__delete-btn"><img src="/assets/icons/bin_faded.png" width="20px" height="20px"></button>
        </li>
    `);
    count++;
}

$("#add-allergen-btn").click(addAllergen); // When the add allergen button is clicked, add a new allergen

addAllergen(); // Run once when the page loads

// Move the labels in the form so they align with their fields
$('div.edit-form__field').each(function () {
    const label = $(this).find('label');
    if ($(this).find('textarea').length > 0) {
        label.css({'margin-top': 0.5*$(this).height()-0.5*label.height()});
    } else {
        label.css({'margin-top': 0.5*$(this).height()});
    }
});
