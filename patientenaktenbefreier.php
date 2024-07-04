<?php
/*
== patientenaktenbefreier ==
Plugin Name: patientenaktenbefreier
Description: Ein WordPress-Plugin, mit dem Versicherte der Ausstellung der elektronischen Patientenakte (ePA) widersprechen können
Version: 1.0.0
Author: Phil Lehmann, AK Vorratsdatenspeicherung
Author URI: http://www.vorratsdatenspeicherung.de/
Contributors: PhilLehmann
Tested up to: 5.2.4
Requires at least: 5.2.4
Requires PHP: 7.1
Stable tag: trunk
*/

defined('ABSPATH') or die('');

// Main entry point (shortcode)

function patientenaktenbefreier_router() {
	$page = 'infos';
	$pages = array('infos', 'form', 'result', 'good-bye');
	if(isset($_GET['gp'])) {
		if(in_array($_GET['gp'], $pages)) {
			$page = $_GET['gp'];			
		} else {
			wp_die('Seite nicht gefunden.');
		}
	}
	
	require_once __DIR__ . '/pages/' . $page . '.php';
}

add_shortcode('patientenaktenbefreier', 'patientenaktenbefreier_router');

// Functions to include

function patientenaktenbefreier_get_mail_text($params, $mediaType) {
	if(!isset($params['gp_name']) || !isset($params['gp_strasse']) || !isset($params['gp_plz']) || !isset($params['gp_ort']) || !isset($params['gp_kasse']) || !isset($params['gp_nummer'])) {
		wp_die('Einer der Parameter "gp_name", "gp_strasse", "gp_plz", "gp_ort", "gp_kasse", "gp_nummer" fehlt.');
	}
	if($params['gp_kasse'] == 'other' && !isset($params['gp_kk_name'])) {
		wp_die('Der Parameter "gp_kk_name" fehlt.');
	}
	
	$vars = [];
	$vars['name'] = esc_html($params['gp_name']);
	$vars['strasse'] = esc_html($params['gp_strasse']);
	$vars['plz'] = esc_html($params['gp_plz']);
	$vars['ort'] = esc_html($params['gp_ort']);
	if($params['gp_kasse'] == 'other') {
		$vars['kasse'] = esc_html($params['gp_kk_name']);
	} else {
		$vars['kasse'] = esc_html($params['gp_kasse']);
	}
	$vars['versichertennummer'] = esc_html($params['gp_nummer']);
	
	$vars['mediaType'] = $mediaType;
	
	extract($vars);
	ob_start();
	echo eval('?>' . get_option('patientenaktenbefreier_mail_text'));
	return ob_get_clean();
}

function patientenaktenbefreier_get_post_form($form_attributes, $content) {
	
	$str = '<form ' . $form_attributes . ' method="post">';
	foreach ($_POST as $key => $value) {
		$str .= '<input type="hidden" name="' . $key . '" value="' . esc_attr($value) . '">';
	}
	$str .= $content . '</form>';
	return $str;
}

// Include CSS and script files

function patientenaktenbefreier_router_enqueue_scripts() {
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('select2-script', plugin_dir_url(__FILE__) . 'assets/select2.min.js', array('jquery'));
	wp_enqueue_style('select2-style', plugin_dir_url(__FILE__) . 'assets/select2.min.css');
	wp_enqueue_script('patientenaktenbefreier-script', plugin_dir_url(__FILE__) . 'assets/scripts.js', array('jquery'));	
	wp_enqueue_style('patientenaktenbefreier-style', plugin_dir_url(__FILE__) . 'assets/style.css');
	
	if(isset($_POST['gp_kasse'])) {
		wp_add_inline_script('patientenaktenbefreier-script', 'jQuery(document).ready(function($){ $(\'.select2.krankenkasse\').val(\'' . esc_js($_POST['gp_kasse']) . '\').trigger(\'change\'); if(\'' . esc_js($_POST['gp_kasse']) . '\' == \'other\') { $(\'.other.fields\').slideDown("slow"); } });');
	}
}

add_action('wp_enqueue_scripts', 'patientenaktenbefreier_router_enqueue_scripts');

// Provide PDF as API, so that WordPress theme is not sent and corrupting the PDF

function patientenaktenbefreier_api() {
	require_once __DIR__ . '/includes/pdf.php';
	register_rest_route('patientenaktenbefreier', 'pdf', array( 
		'methods' => 'POST',
		'callback' => 'patientenaktenbefreier_pdf',
	));
}

add_action('rest_api_init','patientenaktenbefreier_api');

// Start the session so we can remember users that already made a data information request

function patientenaktenbefreier_init() {
	if(!session_id()) {
		session_start();
	}
}
add_action('init', 'patientenaktenbefreier_init');

// Admin section

function patientenaktenbefreier_settings() {
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_threshold', array(
		'type' => 'integer',
		'description' => 'Die Anzahl der Abfragen, ab der der Zähler auf der Info-Seite dargestellt werden soll.',
		'default' => 0,
	));
	
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_info_text', array(
		'type' => 'string',
		'description' => 'Der Text, der auf der Info-Seite dargestellt werden soll.',
		'default' => 'Lorem Ipsum.',
	));
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_mail_subject', array(
		'type' => 'string',
		'description' => 'Der Betreff des Textes, der auf der Ergebnis-Seite, in der PDF und der Mail verwendet werden soll.',
		'default' => 'Lorem Ipsum.',
	));
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_mail_text', array(
		'type' => 'string',
		'description' => 'Der Text, der auf der Ergebnis-Seite, in der PDF und der Mail verwendet werden soll.',
		'default' => 'Lorem Ipsum.',
	));
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_mail_text', array(
		'type' => 'string',
		'description' => 'Der Text, der auf der Ergebnis-Seite, in der PDF und der Mail verwendet werden soll.',
		'default' => 'Lorem Ipsum.',
	));
    register_setting('patientenaktenbefreier_options_section', 'patientenaktenbefreier_good_bye_text', array(
		'type' => 'string',
		'description' => 'Der Text, der auf der letzten Seite dargestellt werden soll.',
		'default' => 'Lorem Ipsum.',
	));
	
	add_settings_section('patientenaktenbefreier_options_section', 'patientenaktenbefreier', 'patientenaktenbefreier_options_section', 'patientenaktenbefreier_options');
	add_settings_field('patientenaktenbefreier_counter', 'Stand des Abfragen-Zählers', 'patientenaktenbefreier_counter_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
	add_settings_field('patientenaktenbefreier_threshold', 'Schwellwert für die Anzeige des Abfragen-Zählers', 'patientenaktenbefreier_threshold_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
	add_settings_field('patientenaktenbefreier_info_text', 'Text der Info-Seite', 'patientenaktenbefreier_info_text_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
	add_settings_field('patientenaktenbefreier_mail_subject', 'Betreff der versendeten Nachricht', 'patientenaktenbefreier_mail_subject_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
	add_settings_field('patientenaktenbefreier_mail_text', 'Text der versendeten Nachricht', 'patientenaktenbefreier_mail_text_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
	add_settings_field('patientenaktenbefreier_good_bye_text', 'Text der letzten Seite', 'patientenaktenbefreier_good_bye_text_render', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_section');
} 
add_action('admin_init', 'patientenaktenbefreier_settings');

function patientenaktenbefreier_add_admin_menu() {
	add_options_page('patientenaktenbefreier', 'patientenaktenbefreier', 'manage_options', 'patientenaktenbefreier_options', 'patientenaktenbefreier_options_page');
}
add_action('admin_menu', 'patientenaktenbefreier_add_admin_menu');

function patientenaktenbefreier_options_page() {
?><div>
	<h1>Einstellungen › patientenaktenbefreier</h1>
	<form action="options.php" method="post">
		<?php settings_fields('patientenaktenbefreier_options_section'); ?>
		<?php do_settings_sections('patientenaktenbefreier_options'); ?>
	 
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>
</div><?php
}

function patientenaktenbefreier_options_section() {}
 
function patientenaktenbefreier_counter_render() {
	echo '<code>' . get_option('patientenaktenbefreier_counter', 0) . '</code>';
}

function patientenaktenbefreier_threshold_render() {
	echo '<input name="patientenaktenbefreier_threshold" id="patientenaktenbefreier_threshold" type="text" value="' . get_option('patientenaktenbefreier_threshold', 0) . '"  />';
}

function patientenaktenbefreier_info_text_render() {
    wp_editor(get_option('patientenaktenbefreier_info_text'), 'patientenaktenbefreier_info_text', array( 
        'textarea_name' => 'patientenaktenbefreier_info_text',
        'media_buttons' => false,
    ));
}

function patientenaktenbefreier_mail_subject_render() {
	echo '<input name="patientenaktenbefreier_mail_subject" id="patientenaktenbefreier_mail_subject" type="text" value="' . get_option('patientenaktenbefreier_mail_subject', 'Datenschutzauskunft') . '"  style="width: 100%;"/>';
}

function patientenaktenbefreier_mail_text_render() {
    wp_editor(get_option('patientenaktenbefreier_mail_text'), 'patientenaktenbefreier_mail_text', array( 
        'textarea_name' => 'patientenaktenbefreier_mail_text',
        'media_buttons' => false,
    ));
?>

<p>
	Dieses Textfeld unterstützt PHP, zum Beispiel <code>&lt;?=$name?&gt;</code> und <code>&lt;?php if($mediaType === "pdf") { echo "Wird nur in der PDF ausgegeben."; } ?&gt;</code>.
	
	Verfügbare Variablen: 
	<ul>
		<li><code>$name</code> für den kompletten Namen des Versicherten</li>
		<li><code>$strasse</code> für die Straße des Versicherten</li>
		<li><code>$plz</code> für die Postleitzahl des Versicherten</li>
		<li><code>$ort</code> für den Ort des Versicherten</li>
		<li><code>$kasse</code> für die Krankenkasse des Versicherten</li>
		<li><code>$versichertennummer</code> für die Versichertennummer</li>
		<li><code>$mediaType</code> für konditionale Formatierungen (mögliche Werte: 'screen', 'pdf' und 'mail')</li>
	</ul>
</p>

<?php
}

function patientenaktenbefreier_good_bye_text_render() {
    wp_editor(get_option('patientenaktenbefreier_good_bye_text'), 'patientenaktenbefreier_good_bye_text', array( 
        'textarea_name' => 'patientenaktenbefreier_good_bye_text',
        'media_buttons' => false,
    ));
}

