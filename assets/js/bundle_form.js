var count = 0;

$(document).on('mouseenter', '.allergen-list__item__delete-btn', function () {
    $(this).find("img").attr("src", "/assets/icons/bin.png");
});

$(document).on('mouseleave', '.allergen-list__item__delete-btn', function () {
    $(this).find("img").attr("src", "/assets/icons/bin_faded.png");
});

$(document).on('change', '.allergen-list__item__selector', function() {
    if ($(this).val() !== "") {
        $(this).removeClass("disabled");
    } else {
        $(this).addClass("disabled");
    }
});

$(document).on('click', '.allergen-list__item__delete-btn', function () {
    $(this).parent().remove();
});

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
$("#add-allergen-btn").click(addAllergen);

addAllergen();

$('div.edit-form__field').each(function () {
    const label = $(this).find('label');
    if ($(this).find('textarea').length > 0) {
        label.css({'margin-top': 0.5*$(this).height()-0.5*label.height()});
    } else {
        label.css({'margin-top': 0.5*$(this).height()});
    }
});
