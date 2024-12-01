jQuery(document).ready(function($) {
    // form storage selection
    $('select[name="opt_out_generator_form_storage"]').on('change', function() {
       this.form.submit();
    });

    // process selection
    $('select[name="process"]').on('change', function() {
       this.form.submit();
    });

    // process deletion
    $('.button-danger').click(function() {
        return confirm('Möchten Sie dieses Opt-Out-Verfahren wirklich endgültig löschen?');
    })

    // process import
    $('.button-import').click(function() {
        $('#import-file-input').click();
    });
    
    // opt-out für dritte
    $thirdPartySelector = $('select[name="opt_out_generator_third_party"]');
    $regularTextRow = $('#wp-opt_out_generator_mail_text-wrap').parent().parent();
    $optOutTextRow = $('#wp-opt_out_generator_third_party_mail_text-wrap').parent().parent();
    $thirdPartyInfoNotice = $('.opt_out_third_party_info_notice');
    var thirdParty = $thirdPartySelector.find(":selected").val();
    if(thirdParty == 'no') {
        $optOutTextRow.slideUp('slow');
    } else if(thirdParty == 'yes') {
        $regularTextRow.slideUp('slow');
    }
    if(thirdParty == 'combo') {
        $thirdPartyInfoNotice.slideDown('slow');
    } else {
        $thirdPartyInfoNotice.slideUp('slow');
    }
    $thirdPartySelector.on('change', function() {
        var thirdParty = $thirdPartySelector.find(":selected").val();
        if(thirdParty == 'no') {
            $optOutTextRow.slideUp('slow');
            $regularTextRow.slideDown('slow');
        } else if(thirdParty == 'yes') {
            $optOutTextRow.slideDown('slow');
            $regularTextRow.slideUp('slow');
        } else {
            $optOutTextRow.slideDown('slow');
            $regularTextRow.slideDown('slow');
        }
        if(thirdParty == 'combo') {
            $thirdPartyInfoNotice.slideDown('slow');
        } else {
            $thirdPartyInfoNotice.slideUp('slow');
        }
     });

    // krankenkassen-hinweise
    $krankenkassenSelector = $('.select2.krankenkasse');
	$krankenkassenSelector.select2({
		placeholder: 'Wähle eine Krankenkasse aus, um den Hinweis zu editieren...',
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
            $($.grep(alleHinweise, (el) => $(el).data('name') != krankenkasse)).slideUp('slow');
            $('.krankenkassen-hinweis[data-name="' + krankenkasse + '"]').slideDown('slow');		
        } else {
            alleHinweise.slideUp('slow');
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
    
    // messages should be shown only once
    const url = new URL(window.location.href);
    if(url.searchParams.has('msg')) {
        url.searchParams.delete('msg');
        window.history.replaceState(null, '', url.href);
    }
});