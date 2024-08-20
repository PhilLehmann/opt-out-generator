
jQuery(document).ready(function($) {
    // process selection
    $('select[name="process"]').on('change', function() {
       this.form.submit();
    });
});