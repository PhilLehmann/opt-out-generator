<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$processes = get_option('opt_out_generator_processes', []);
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

require_once __DIR__ . '/../includes/validator.php';

function opt_out_generator_getError($name) {
	if(opt_out_generator_Validator::hasError($name)) {
		return '<i class="error">' . opt_out_generator_Validator::getError($name) . '</i>';
	}
}

function opt_out_generator_getValue($name) {
	$html = '<input type="text" name="' . $name . '"';
	if(isset($_POST[$name])) {
		$html .= ' value="' . esc_attr($_POST[$name]) . '"';
	}
	$error = opt_out_generator_getError($name);
	if($error) {
		$html .= ' class="error"';
	}
	$html .= '/>';
	$html .= $error;
	return $html;
}

if(isset($_POST['gp_kasse'])) {
	wp_add_inline_script('opt_out_generator-script', 'jQuery(document).ready(function($){ $(\'.select2.krankenkasse\').val(\'' . esc_js($_POST['gp_kasse']) . '\').trigger("change"); });');
}

?>
<div class="opt-out-generator form">
	<form action="?gp=result" method="post">
		<?php
			if(isset($showResult) && !opt_out_generator_Validator::isValidPost()) {
				echo '<div class="error">Da ist etwas schiefgelaufen.</div>';
			}
		?>
		
		Name: <?=opt_out_generator_getValue("gp_name")?><br/>
		Straße: <?=opt_out_generator_getValue("gp_strasse")?><br/>
		Postleitzahl: <?=opt_out_generator_getValue("gp_plz")?><br/>
		Ort: <?=opt_out_generator_getValue("gp_ort")?><br/>
		
		<p><hr/></p>
		
		Krankenkasse: 
		<select class="select2 krankenkasse" name="gp_kasse">
			<option></option>
			<?php
				require_once __DIR__ . '/../includes/krankenkassen.php';
				$opt_out_generator_krankenkassen = opt_out_generator_Krankenkassenliste::getInstance();
				$opt_out_generator_krankenkassen->printOptions();
			?>
			<option value="other">Andere...</option>
		</select>
		<?=opt_out_generator_getError("gp_kasse")?><br/>
		<div class="other fields hidden">
			Name: <?=opt_out_generator_getValue("gp_kk_name")?><br/>
			Straße: <?=opt_out_generator_getValue("gp_kk_strasse")?><br/>
			Postleitzahl: <?=opt_out_generator_getValue("gp_kk_plz")?><br/>
			Ort: <?=opt_out_generator_getValue("gp_kk_ort")?><br/>
			E-Mail: <?=opt_out_generator_getValue("gp_kk_mail")?><br/>
		</div>
		Versichertennummer: <?=opt_out_generator_getValue("gp_nummer")?><br/>
        
        <div class="actions">
            <input class="infos button" type="button" value="&lt; Zurück" />
            <input class="submit button" type="submit" value="<?=esc_attr($process['form_button'])?>" />
        </div>
	</form>
	<p>
	</p>
</div>