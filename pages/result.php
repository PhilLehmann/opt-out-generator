<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$formStorage = opt_out_generator_get_form_storage();
$processes = opt_out_generator_get_processes();
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

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
        $processes[OPT_OUT_GENERATOR_PROCESS_ID]['counter']++;
        update_option('opt_out_generator_processes', $processes);
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
		<b><?=esc_html($process['mail_subject'])?></b>
	</p>

	<div class="mail-text">
		<?=wpautop(opt_out_generator_get_mail_text_from_post())?>
	</div>
    <?php
    if(isset($process['krankenkassen_hinweise'][$krankenkasse->name]) && trim($process['krankenkassen_hinweise'][$krankenkasse->name]) !== '') {
        echo '<div class="opt-out-generator-hinweis hidden auto-show"><p><b>' . $process['krankenkassen_hinweis_titel'] . '</b></p>' . wpautop($process['krankenkassen_hinweise'][$krankenkasse->name]) . '</div>';
    }
    ?>
	<div class="mail-to hidden">
		<?=esc_html($krankenkasse->email)?>
	</div>
	<div class="mail-subject hidden">
		<?=esc_html($process['mail_subject'])?>
	</div>

	<div class="actions">
		<?php
		echo opt_out_generator_get_post_form('action="?gp=form' . (isset($_GET['tp']) ? '&tp=1' : '') . '"', '<input class="submit button" type="button" value="&lt; Zurück" />');
		if($krankenkasse->canSendLetter()) {
			echo opt_out_generator_get_post_form('target="_blank" action="' . get_site_url() . '/wp-json/opt-out-generator/pdf"', 
                                                 '<input type="hidden" name="process" value="' . OPT_OUT_GENERATOR_PROCESS_ID . '" />
                                                  <input class="submit button" type="button" value="PDF öffnen" />');
		}
		if($krankenkasse->canSendMail() && $process['send_mail'] === 1) {
		?>
		<form><input class="mail button" type="button" value="Mail öffnen" /></form>
		<?php
		}
		echo opt_out_generator_get_post_form('action="?gp=good-bye' . (isset($_GET['tp']) ? '&tp=1' : '') . '"', '<input class="submit button" type="button" value="Weitere Infos &gt;" />');
		?>
	</div>
</div>
<script type="text/javascript">
    <?php
        if($formStorage == 'session' || $formStorage == 'local') {
            echo 'var formData = ' . json_encode($_POST) . ';';
            echo $formStorage . 'Storage.setItem("opt-out-generator-form-data", JSON.stringify(formData));';
        }

        if($formStorage != 'session') {
            echo 'sessionStorage.removeItem("opt-out-generator-form-data");';
        }
        if($formStorage != 'local') {
            echo 'localStorage.removeItem("opt-out-generator-form-data");';
        }
    ?>
</script>

<?php
}