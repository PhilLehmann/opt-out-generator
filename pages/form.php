<?php

defined('ABSPATH') or die('');

require_once __DIR__ . '/../includes/validator.php';

function patientenaktenbefreier_getError($name) {
	if(patientenaktenbefreier_Validator::hasError($name)) {
		return '<i class="error">' . patientenaktenbefreier_Validator::getError($name) . '</i>';
	}
}

function patientenaktenbefreier_getValue($name) {
	$html = '<input type="text" name="' . $name . '"';
	if(isset($_POST[$name])) {
		$html .= ' value="' . esc_attr($_POST[$name]) . '"';
	}
	$error = patientenaktenbefreier_getError($name);
	if($error) {
		$html .= ' class="error"';
	}
	$html .= '/>';
	$html .= $error;
	return $html;
}

if(isset($_POST['gp_kasse'])) {
	wp_add_inline_script('patientenaktenbefreier-script', 'jQuery(document).ready(function($){ $(\'.select2.krankenkasse\').val(\'' . esc_js($_POST['gp_kasse']) . '\').trigger("change"); });');
}

?>
<div class="patientenaktenbefreier form">
	<form action="?gp=result" method="post">
		<?php
			if(isset($showResult) && !patientenaktenbefreier_Validator::isValidPost()) {
				echo '<div class="error">Da ist etwas schiefgelaufen.</div>';
			}
		?>
		
		Name: <?=patientenaktenbefreier_getValue("gp_name")?><br/>
		Straße: <?=patientenaktenbefreier_getValue("gp_strasse")?><br/>
		Postleitzahl: <?=patientenaktenbefreier_getValue("gp_plz")?><br/>
		Ort: <?=patientenaktenbefreier_getValue("gp_ort")?><br/>
		
		<p><hr/></p>
		
		Krankenkasse: 
		<select class="select2 krankenkasse" name="gp_kasse">
			<option></option>
			<?php
				require_once __DIR__ . '/../includes/krankenkassen.php';
				$patientenaktenbefreier_krankenkassen = patientenaktenbefreier_Krankenkassenliste::getInstance();
				$patientenaktenbefreier_krankenkassen->printOptions();
			?>
			<option value="other">Andere...</option>
		</select>
		<?=patientenaktenbefreier_getError("gp_kasse")?><br/>
		<div class="other fields">
			Name: <?=patientenaktenbefreier_getValue("gp_kk_name")?><br/>
			Straße: <?=patientenaktenbefreier_getValue("gp_kk_strasse")?><br/>
			Postleitzahl: <?=patientenaktenbefreier_getValue("gp_kk_plz")?><br/>
			Ort: <?=patientenaktenbefreier_getValue("gp_kk_ort")?><br/>
			E-Mail: <?=patientenaktenbefreier_getValue("gp_kk_mail")?><br/>
		</div>
		Versichertennummer: <?=patientenaktenbefreier_getValue("gp_nummer")?><br/>
		<input class="submit" type="submit" value="Anfrage erstellen" />
	</form>
	<p>
		<input class="form infos button" type="button" value="&lt; Zurück" />
	</p>
</div>