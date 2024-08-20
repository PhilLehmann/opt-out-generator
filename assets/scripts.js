
jQuery(document).ready(function($) {
	// buttons
	$('.infos.button').click(function() {
		window.location = "?gp=infos";
	});
	$('.form.button').click(function() {
		window.location = "?gp=form";
	});
	$('.submit.button').click(function() {
		this.form.submit();
	});
    $('.mail.button').click(function() {
        window.open("mailto:" + $('.mail-to').text() + "?subject=" + encodeURIComponent($('.mail-subject').text().trim()) + "&body=" + encodeURIComponent($('.mail-text').text().trim()));
	});

	// form
    $el = $('.select2.krankenkasse');
	$el.select2({
		placeholder: ""
	}).on('select2:select', function (e) {
		if(e.params.data.id == 'other') {
			$('.other.fields').slideDown("slow");
		} else {
			$('.other.fields').slideUp("slow");			
		}		
	});
});