<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$processes = opt_out_generator_get_processes();
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

$isThirdParty = isset($process['third_party']) && ($process['third_party'] == 'yes' || ($process['third_party'] == 'combo' && isset($_GET['tp'])));

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

?>
<div class="opt-out-generator form">
	<form action="?gp=result<?=isset($_GET['tp']) ? '&tp=1': ''?>" method="post">
		<?php
			if(isset($showResult) && !opt_out_generator_Validator::isValidPost()) {
				echo '<div class="error">Da ist etwas schiefgelaufen.</div>';
			}
		?>
        <div class="opt-out-generator-hinweis hidden">
            Es gibt gespeicherte Formulardaten für <i class="name"></i>. Möchten Sie diese verwenden?
            <div>
                <a class="button button-yes"><div class="checkmark"></div> Ja</a>
                <a class="button button-no"><div class="crossmark"></div> Nein</a>
            </div>
        </div>

        <?php
            if($isThirdParty) {
                echo '<h3>Angaben zur versicherten Person</h3>';
            }
        ?>
		
		Name: <?=opt_out_generator_getValue("gp_name")?><br/>
		Straße &amp; Hausnummer: <?=opt_out_generator_getValue("gp_strasse")?><br/>
		Postleitzahl: <?=opt_out_generator_getValue("gp_plz")?><br/>
		Ort: <?=opt_out_generator_getValue("gp_ort")?>
		
		<hr/>
		
		Krankenkasse: 
		<select class="select2 krankenkasse" name="gp_kasse">
			<option></option>
			<?php
				require_once __DIR__ . '/../includes/krankenkassen.php';
				$opt_out_generator_krankenkassen = opt_out_generator_Krankenkassenliste::getInstance();
				$opt_out_generator_krankenkassen->printOptions($process['private_krankenkassen']);
			?>
			<option value="other">Andere...</option>
		</select>
		<?=opt_out_generator_getError("gp_kasse")?><br/>
		<div class="other fields hidden">
			Name: <?=opt_out_generator_getValue("gp_kk_name")?><br/>
			Straße &amp; Hausnummer: <?=opt_out_generator_getValue("gp_kk_strasse")?><br/>
			Postleitzahl: <?=opt_out_generator_getValue("gp_kk_plz")?><br/>
			Ort: <?=opt_out_generator_getValue("gp_kk_ort")?><br/>
			E-Mail: <?=opt_out_generator_getValue("gp_kk_mail")?><br/>
		</div>
		Versichertennummer: <?=opt_out_generator_getValue("gp_versichertennummer")?><br/>
        
        <?php
            if($isThirdParty) {
        ?>
                <h3>Angaben zur Vertretung</h3>

                Die Vertretung besteht aufgrund von... 
                <select name="gp_vertretungsart">
                    <option value="eltern" <?=isset($_POST['gp_vertretungsart']) && $_POST['gp_vertretungsart'] == 'eltern' ? 'selected="selected"' : '' ?>>Elternschaft</option>';
                    <option value="betreuung" <?=isset($_POST['gp_vertretungsart']) && $_POST['gp_vertretungsart'] == 'betreuung' ? 'selected="selected"' : '' ?>>gesetzlicher Betreuung</option>';
                </select>

                <h3>Vertreter 1</h3>

                <input type="checkbox" id="vertretung1_wohnort_wie_gp" name="vertretung1_wohnort_wie_gp" value="1" <?=checked(isset($_POST['vertretung1_wohnort_wie_gp']) && $_POST['vertretung1_wohnort_wie_gp'] == '1', true, false)?>/>
                <label for="vertretung1_wohnort_wie_gp">Adresse wie versicherte Person?</label><br/>
                Name: <?=opt_out_generator_getValue("vertretung1_name")?><br/>
                <div class="vertretung1 fields hidden">
                    Straße &amp; Hausnummer: <?=opt_out_generator_getValue("vertretung1_strasse")?><br/>
                    Postleitzahl: <?=opt_out_generator_getValue("vertretung1_plz")?><br/>
                    Ort: <?=opt_out_generator_getValue("vertretung1_ort")?><br/>
                </div>
                
                <h3>ggf. Vertreter 2</h3>

                <input type="checkbox" id="vertretung2_wohnort_wie_gp" name="vertretung2_wohnort_wie_gp" value="1" <?=checked(isset($_POST['vertretung2_wohnort_wie_gp']) && $_POST['vertretung2_wohnort_wie_gp'] == '1', true, false)?>/>
                <label for="vertretung2_wohnort_wie_gp">Adresse wie versicherte Person?</label><br/>
                Name: <?=opt_out_generator_getValue("vertretung2_name")?><br/>
                <div class="vertretung2 fields hidden">
                    Straße &amp; Hausnummer: <?=opt_out_generator_getValue("vertretung2_strasse")?><br/>
                    Postleitzahl: <?=opt_out_generator_getValue("vertretung2_plz")?><br/>
                    Ort: <?=opt_out_generator_getValue("vertretung2_ort")?><br/>
                </div>
        <?php
            }
        ?>
        <div class="actions">
            <input class="infos button <?=isset($_GET['tp']) ? 'oog-third-party' : '' ?>" type="button" value="&lt; Zurück" />
            <input class="submit button <?=isset($_GET['tp']) ? 'oog-third-party' : '' ?>" type="submit" value="<?=esc_attr($process['form_button'])?>" />
        </div>
	</form>
	<p>
	</p>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($){ $('.select2.krankenkasse').val('<?=esc_js($_POST['gp_kasse']) ?>').trigger("change"); });
</script>