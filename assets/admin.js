
jQuery(document).ready(function($) {
    // process selection
    $('select[name="process"]').on('change', function() {
       this.form.submit();
    });

    // process deletion
    $('.button-danger').click(function() {
        return confirm("Möchten Sie dieses Opt-Out-Verfahren wirklich endgültig löschen?");
    })

    // process import
    $('.button-import').click(function() {
        $('#import-file-input').click();
    });
    
    // krankenkassen-hinweise
    $krankenkassenSelector = $('.select2.krankenkasse');
	$krankenkassenSelector.select2({
		placeholder: "Wähle eine Krankenkasse aus, um den Hinweis zu editieren...",
        templateResult: function(data, container) {
            if (data.element) {
                $(container).addClass($(data.element).attr('class'));
            }
            return data.text;
        }
	}).on('select2:select', function (e) {
        var krankenkasse = e.params.data.id;
        var alleHinweise = $('.krankenkassen-hinweis');
        if(krankenkasse) {
            $($.grep(alleHinweise, (el) => $(el).data('name') != krankenkasse)).slideUp("slow");
            $('.krankenkassen-hinweis[data-name="' + krankenkasse + '"]').slideDown("slow");		
        } else {
            alleHinweise.slideUp("slow");
        }
	});

    $showAll = $('.show-all');
    $showNone = $('.show-none');
    $showAll.click(function() {
        $showAll.hide();
        $showNone.show();
        $('.krankenkassen-hinweis').show();
        $('.krankenkassen-name').show();
        $krankenkassenSelector.val('');
    });
    $showNone.click(function() {
        $showNone.hide();
        $showAll.show();
        $('.krankenkassen-hinweis').hide();
        $('.krankenkassen-name').hide();
        $krankenkassenSelector.val('');
    });
});