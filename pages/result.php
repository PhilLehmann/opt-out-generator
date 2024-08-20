<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$showResult = true;

require_once __DIR__ . '/../includes/validator.php';
if(!opt_out_generator_Validator::isValidPost()) {
	$showResult = false;
	require_once __DIR__ . '/form.php';
}

if($showResult) {
    
    // Start the session so we can remember users that already made a data information request (this set's a cookie!)

    if(!session_id()) {
		session_start();
	}

	if(!isset($_SESSION['opt_out_generator_session_request_counted_' . OPT_OUT_GENERATOR_PROCESS_ID])) {
		$_SESSION['opt_out_generator_session_request_counted_' . OPT_OUT_GENERATOR_PROCESS_ID] = 1;
		$counter = get_option('opt_out_generator_counter_' . OPT_OUT_GENERATOR_PROCESS_ID, 0);
		update_option('opt_out_generator_counter_' . OPT_OUT_GENERATOR_PROCESS_ID, $counter + 1);
	}

	require_once __DIR__ . '/../includes/krankenkassen.php';
	$opt_out_generator_krankenkassen = opt_out_generator_Krankenkassenliste::getInstance();
	$krankenkasse = $opt_out_generator_krankenkassen->getFromPost();

?>
<div class="opt-out-generator result">
	<p>
		<b><?=$krankenkasse->name?></b><br/>
		<?=$krankenkasse->strasse?><br/>
		<?=$krankenkasse->plz?> <?=$krankenkasse->ort?><br/>
		<i><?=$krankenkasse->email?></i>
	</p>
	
	<p>
		<b><?=esc_html(get_option('opt_out_generator_mail_subject_' . OPT_OUT_GENERATOR_PROCESS_ID))?></b>
	</p>

	<?=wpautop(opt_out_generator_get_mail_text($_POST, 'screen'))?>

	<div class="mail-to hidden">
		<?=esc_html($krankenkasse->email)?>
	</div>
	<div class="mail-subject hidden">
		<?=esc_html(get_option('opt_out_generator_mail_subject_' . OPT_OUT_GENERATOR_PROCESS_ID))?>
	</div>
	<div class="mail-text hidden">
		<?=wpautop(opt_out_generator_get_mail_text($_POST, 'mail'))?>
	</div>

	<div class="actions">
		<?php
		echo opt_out_generator_get_post_form('action="?gp=form"', '<input class="submit button" type="button" value="&lt; Zurück" />');
		if($krankenkasse->canSendLetter()) {
			echo opt_out_generator_get_post_form('target="_blank" action="' . get_site_url() . '/wp-json/opt-out-generator/pdf"', 
                                                 '<input type="hidden" name="process" value="' . OPT_OUT_GENERATOR_PROCESS_ID . '" />
                                                  <input class="submit button" type="button" value="PDF öffnen" />');
		}
		if($krankenkasse->canSendMail()) {
		?>
		<form><input class="mail button" type="button" value="Mail öffnen" /></form>
		<?php
		}
		echo opt_out_generator_get_post_form('action="?gp=good-bye"', '<input class="submit button" type="button" value="Weitere Infos" />');
		?>
	</div>
</div>

<?php
}