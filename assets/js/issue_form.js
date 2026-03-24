$("#issue-text").val('');

$('.issue-form__field').each(function () {
    const label = $(this).find('label');
    const span = $(this).find('*:not(label)');
    
    if (span !== undefined) {
        const diff = $(this).width() - label.width();
        const width = (diff > 300) ? 300 : diff;
        span.css({'width': width});
    }
})

$("#clear-btn").click(() => {
    $("#issue-title").val('');
    $("#issue-text").val('');
});
