<?php

defined('ABSPATH') or die('');

$showResult = true;

require_once __DIR__ . '/../includes/validator.php';
if(!patientenaktenbefreier_Validator::isValidPost()) {
	$showResult = false;
	require_once __DIR__ . '/form.php';
}

if($showResult) {
	
	if(!isset($_SESSION['patientenaktenbefreier_session_request_counted'])) {
		$_SESSION['patientenaktenbefreier_session_request_counted'] = 1;
		$counter = get_option('patientenaktenbefreier_counter', 0);
		update_option('patientenaktenbefreier_counter', $counter + 1);
	}

	require_once __DIR__ . '/../includes/krankenkassen.php';
	$patientenaktenbefreier_krankenkassen = patientenaktenbefreier_Krankenkassenliste::getInstance();
	$krankenkasse = $patientenaktenbefreier_krankenkassen->getFromPost();

?>
<div class="patientenaktenbefreier result">
	<p>
		<b><?=$krankenkasse->name?></b><br/>
		<?=$krankenkasse->strasse?><br/>
		<?=$krankenkasse->plz?> <?=$krankenkasse->ort?><br/>
		<i><?=$krankenkasse->email?></i>
	</p>
	
	<p>
		<b><?=esc_html(get_option('patientenaktenbefreier_mail_subject', 'Datenschutzauskunft'))?></b>
	</p>

	<?=wpautop(patientenaktenbefreier_get_mail_text($_POST, 'screen'))?>

	<div class="mail-to">
		<?=esc_html($krankenkasse->email)?>
	</div>
	<div class="mail-subject">
		<?=esc_html(get_option('patientenaktenbefreier_mail_subject', 'Datenschutzauskunft'))?>
	</div>
	<div class="mail-text">
		<?=wpautop(patientenaktenbefreier_get_mail_text($_POST, 'mail'))?>
	</div>

	<div class="actions">
		<?php
		echo patientenaktenbefreier_get_post_form('action="?gp=form"', '<input class="infos button" type="button" value="&lt; Zurück" />');
		if($krankenkasse->canSendLetter()) {
			echo patientenaktenbefreier_get_post_form('target="_blank" action="/wp-json/patientenaktenbefreier/pdf"', '<input class="submit-pdf button" type="submit" value="PDF öffnen" />');
		}
		if($krankenkasse->canSendMail()) {
		?>
		<input class="submit-mail button" type="button" value="Mail öffnen" />
		<?php
		}
		?>
		<input class="good-bye button" type="button" value="Weitere Infos" />
	</div>
</div>

<?php
}