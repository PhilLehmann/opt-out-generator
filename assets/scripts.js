
jQuery(document).ready(function($) {
	// buttons
	$('.infos.button').click(function() {
		window.location = '?gp=infos';
	});
	$('.form.button').click(function() {
		window.location = '?gp=form';
	});
	$('.submit.button').click(function() {
		this.form.submit();
	});
    $('.mail.button').click(function() {
        window.open('mailto:' + $('.mail-to').text() + '?subject=' + encodeURIComponent($('.mail-subject').text().trim()) + '&body=' + encodeURIComponent($('.mail-text').text().trim()));
	});

	// form
    var $form = $('.opt-out-generator.form > form');
    var $select = $form.find('.select2.krankenkasse');
    $select.select2({
		placeholder: ''
	}).on('select2:select', function (e) {
		if(e.params.data.id == 'other') {
			$('.other.fields').slideDown('slow');
		} else {
			$('.other.fields').slideUp('slow');			
		}		
	});

    if($form.length > 0) {
        var formData = localStorage.getItem('opt-out-generator-form-data');
        var nameInput = $form.find('input[name="gp_name"]');
        if(formData && nameInput.val() == '') {
            $form.find('.opt-out-generator-hinweis').addClass('auto-show');
            $form.find('.opt-out-generator-hinweis .name').text(JSON.parse(formData)?.gp_name);

            $form.find('.opt-out-generator-hinweis .button-yes').on('click', function() {
                formData = JSON.parse(formData);
                for (const [key, val] of Object.entries(formData)) {
                    const input = $form[0].elements[key];
                    input.value = val;
                    if (input.type.indexOf('select') === 0) {
                        // select2 components might have type something along 'select-one'
                        $select.trigger('change');
                    }
                }
                $form.find('.opt-out-generator-hinweis').slideUp();
            });

            $form.find('.opt-out-generator-hinweis .button-no').on('click', function() {
                $form.find('.opt-out-generator-hinweis').slideUp();
            });

        }
    }

    // result
    $('.opt-out-generator.result .opt-out-generator-hinweis a').attr('target', '_blank');

    // hinweise
    $('.opt-out-generator-hinweis.auto-show').slideDown('slow');
});
