jQuery(document).ready(function () {
    jQuery('.handlediv').click(function () {
        if (jQuery(this).parent().hasClass('closed')) {
            jQuery(this).parent().removeClass('closed');
        } else {
            jQuery(this).parent().addClass('closed');
        }
    })
});