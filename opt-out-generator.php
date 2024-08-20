<?php
/*
== opt-out-generator ==
Plugin Name: opt-out-generator
Description: Ein WordPress-Plugin, mit dem Opt-Out-Prozesse gegenüber Krankenkassen (z.B. ePA) umgesetzt werden können
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

function opt_out_generator_router($attrs) {
    $processes = get_option('opt_out_generator_processes', []);
    if(!isset($attrs[0]) || !ctype_digit($attrs[0]) || count($processes) <= intval($attrs[0])) {
        return 'Dem Shortcode wurde keine gültige Opt-Out-Generator Verfahrens-ID mitgegeben.';
    }

    define('OPT_OUT_GENERATOR_PROCESS_ID', $attrs[0]);

	$page = 'infos';
	$pages = array('infos', 'form', 'result', 'good-bye');
	if(isset($_GET['gp'])) {
		if(in_array($_GET['gp'], $pages)) {
			$page = $_GET['gp'];			
		} else {
			wp_die('Seite nicht gefunden.');
		}
	}

	ob_start();
	require_once __DIR__ . '/pages/' . $page . '.php';
    return ob_get_clean();
}

add_shortcode('opt_out_generator', 'opt_out_generator_router');

// Functions to include

function opt_out_generator_get_mail_text($params, $mediaType) {
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
	echo eval('?>' . get_option('opt_out_generator_mail_text_' . OPT_OUT_GENERATOR_PROCESS_ID));
	return ob_get_clean();
}

function opt_out_generator_get_post_form($form_attributes, $content) {
	
	$str = '<form ' . $form_attributes . ' method="post">';
	foreach ($_POST as $key => $value) {
		$str .= '<input type="hidden" name="' . $key . '" value="' . esc_attr($value) . '">';
	}
	$str .= $content . '</form>';
	return $str;
}

function opt_out_generator_get_options_url($params = []) {
    $params['page'] = 'opt_out_generator_options';
    return add_query_arg(
        $params,
		get_admin_url() . 'options-general.php'
	);
}

function opt_out_generator_get_process($arr = null) {
    if($arr == null) {
        $arr = $_GET;
    }
    $processes = get_option('opt_out_generator_processes', []);
    if(isset($arr['process']) && ctype_digit($arr['process']) && count($processes) > intval($arr['process'])) {
        return intval($arr['process']);
    } else {
        return '';
    }
}

// Include CSS and script files

function opt_out_generator_router_enqueue_scripts() {
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('select2-script', plugin_dir_url(__FILE__) . 'assets/select2.min.js', array('jquery'));
	wp_enqueue_style('select2-style', plugin_dir_url(__FILE__) . 'assets/select2.min.css');
	wp_enqueue_script('opt-out-generator-script', plugin_dir_url(__FILE__) . 'assets/scripts.js', array('jquery'));	
	wp_enqueue_style('opt-out-generator-style', plugin_dir_url(__FILE__) . 'assets/style.css');
	
	if(isset($_POST['gp_kasse'])) {
		wp_add_inline_script('opt-out-generator-script', 'jQuery(document).ready(function($){ $(\'.select2.krankenkasse\').val(\'' . esc_js($_POST['gp_kasse']) . '\').trigger(\'change\'); if(\'' . esc_js($_POST['gp_kasse']) . '\' == \'other\') { $(\'.other.fields\').slideDown("slow"); } });');
	}
}

add_action('wp_enqueue_scripts', 'opt_out_generator_router_enqueue_scripts');

// Provide PDF as API, so that WordPress theme is not sent and corrupting the PDF

function opt_out_generator_api() {
	require_once __DIR__ . '/includes/pdf.php';
	register_rest_route('opt-out-generator', 'pdf', array( 
		'methods' => 'POST',
		'callback' => 'opt_out_generator_pdf',
	));
}

add_action('rest_api_init','opt_out_generator_api');

// Admin section

function opt_out_generator_init_admin() {
    $processes = get_option('opt_out_generator_processes', []);

    if(isset($_GET['action']) && $_GET['action'] == 'create_process') {
        $count = array_push($processes, 'Neuer Prozess');
        $processId = $count - 1;
        update_option('opt_out_generator_processes', $processes);

        add_option('opt_out_generator_name_' . $processId, 'Opt-Out-Verfahren');
        add_option('opt_out_generator_threshold_' . $processId, 0);
        add_option('opt_out_generator_info_text_' . $processId, 'Lorem Ipsum.');
        add_option('opt_out_generator_info_button_' . $processId, 'Frage deine Daten ab!');
        add_option('opt_out_generator_form_button_' . $processId, 'Anfrage erstellen');
        add_option('opt_out_generator_mail_subject_' . $processId, 'Lorem Ipsum.');
        add_option('opt_out_generator_mail_text_' . $processId, 'Lorem Ipsum.');
        add_option('opt_out_generator_good_bye_text_' . $processId, 'Lorem Ipsum.');

        wp_redirect(opt_out_generator_get_options_url([ 'process' => $processId ]));
        exit;
    }

    $processId = opt_out_generator_get_process();
    if($processId !== '') {

        // Whitelist settings

        register_setting('opt_out_generator_options_section', 'opt_out_generator_name_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Name des Opt-Out-Verfahrens.',
            'default' => 'Neues Opt-Out-Verfahren',
        ));

        register_setting('opt_out_generator_options_section', 'opt_out_generator_threshold_' . $processId, array(
            'type' => 'integer',
            'description' => 'Die Anzahl der Abfragen, ab der der Zähler auf der Info-Seite dargestellt werden soll.',
            'default' => 0,
        ));
        
        register_setting('opt_out_generator_options_section', 'opt_out_generator_info_text_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Text, der auf der Info-Seite dargestellt werden soll.',
            'default' => 'Lorem Ipsum.',
        ));
        register_setting('opt_out_generator_options_section', 'opt_out_generator_info_button_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Text, der auf dem Button auf der Info-Seite dargestellt werden soll.',
            'default' => 'Frage deine Daten ab!',
        ));
        register_setting('opt_out_generator_options_section', 'opt_out_generator_form_button_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Text, der auf dem Button auf der Formular-Seite dargestellt werden soll.',
            'default' => 'Anfrage erstellen',
        ));
        
        register_setting('opt_out_generator_options_section', 'opt_out_generator_mail_subject_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Betreff des Textes, der auf der Ergebnis-Seite, in der PDF und der Mail verwendet werden soll.',
            'default' => 'Lorem Ipsum.',
        ));
        register_setting('opt_out_generator_options_section', 'opt_out_generator_mail_text_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Text, der auf der Ergebnis-Seite, in der PDF und der Mail verwendet werden soll.',
            'default' => 'Lorem Ipsum.',
        ));
        register_setting('opt_out_generator_options_section', 'opt_out_generator_good_bye_text_' . $processId, array(
            'type' => 'string',
            'description' => 'Der Text, der auf der "Weitere Infos"-Seite dargestellt werden soll.',
            'default' => 'Lorem Ipsum.',
        ));
        
        // Configure options screen

        $processName = $processes[$processId];
        $args = [
            'processes' => $processes,
            'processId' => $processId,
            'processName' => $processName
        ];

        add_settings_section('opt_out_generator_options_section', 'Opt-Out-Verfahren <u>' . $processName . '</u>: ', 'opt_out_generator_options_section', 'opt_out_generator_options');
        add_settings_field('opt_out_generator_shortcode_' . $processId, 'Shortcode', 'opt_out_generator_shortcode_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_name_' . $processId, 'Name des Opt-Out-Verfahrens', 'opt_out_generator_name_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_action('update_option_opt_out_generator_name_' . $processId, 'opt_out_generator_update_name', 10, 2);
        add_action('add_option_opt_out_generator_name_' . $processId, 'opt_out_generator_add_name', 10, 2);
        add_settings_field('opt_out_generator_counter_' . $processId, 'Stand des Abfragen-Zählers', 'opt_out_generator_counter_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_threshold_' . $processId, 'Schwellwert für die Anzeige des Abfragen-Zählers', 'opt_out_generator_threshold_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_info_text_' . $processId, 'Text der "Info"-Seite', 'opt_out_generator_info_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_info_button_' . $processId, 'Button-Text auf der "Info"-Seite', 'opt_out_generator_info_button_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_form_button_' . $processId, 'Button-Text auf der Formular-Seite', 'opt_out_generator_form_button_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_mail_subject_' . $processId, 'Betreff der versendeten Nachricht', 'opt_out_generator_mail_subject_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_mail_text_' . $processId, 'Text der versendeten Nachricht', 'opt_out_generator_mail_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
        add_settings_field('opt_out_generator_good_bye_text_' . $processId, 'Text der "Weitere Infos"-Seite', 'opt_out_generator_good_bye_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    }
} 
add_action('admin_init', 'opt_out_generator_init_admin');

function opt_out_generator_update_name($oldValue, $newValue) {
    $processes = get_option('opt_out_generator_processes', []);
    $processId = opt_out_generator_get_process();
    if($processId !== '') {
        $processes[$processId] = $newValue;
        update_option('opt_out_generator_processes', $processes);
    }
}

function opt_out_generator_add_name($option, $value) {
    opt_out_generator_update_name(null, $value);
}

function opt_out_generator_add_admin_menu() {
	add_options_page('Einstellungen › opt-out-generator', 'opt-out-generator', 'manage_options', 'opt_out_generator_options', 'opt_out_generator_options_page');
}
add_action('admin_menu', 'opt_out_generator_add_admin_menu');

function opt_out_generator_options_page() {
    
?><div class="wrap">
	<h1>Einstellungen › opt-out-generator</h1>
    <h2>Liste der Opt-Out-Verfahren</h2>
    <form action="options-general.php" method="get">
        <input type="hidden" name="page" value="opt_out_generator_options" />
        <table class="form-table processes" role="presentation">
            <tbody>
                <tr>
                    <td><?php
        
        $processes = get_option('opt_out_generator_processes', []);
        $processId = opt_out_generator_get_process();
        if(count($processes) > 0) {
            echo '<select id="opt_out_generator_process" name="process">';
            if($processId !== '') {
                $selectedIndex = $processId;
            } else {
                $selectedIndex = -1;
                echo '<option value="" selected="selected">Bitte wähle einen Prozess aus</option>';
            }
            foreach($processes as $index => $process) {
                echo '<option value="' . $index . '" ' . ($selectedIndex == $index ? 'selected="selected"' : '') . '>' . $process . '</option>';
            }
            echo "</select>";
        }
        echo 'Füge ein <a href="' . esc_url(opt_out_generator_get_options_url([ 'action' => 'create_process' ])) . '">neues Opt-Out-Verfahren</a> hinzu.';
        
                ?></td>
                </tr>
            </tbody>
        </table>
    </form>
    <form action="options.php?process=<?=opt_out_generator_get_process()?>" method="post">
        <?php settings_fields('opt_out_generator_options_section'); ?>
        <?php do_settings_sections('opt_out_generator_options'); ?>
    
        <?php 
        if($processId !== '') {
            echo '<input name="Submit" type="submit" value="' . esc_attr(translate('Save Changes')) . '" />';
        }
        ?>
    </form>
</div><?php
}

function opt_out_generator_enqueue_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('opt-out-generator-admin-script', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'));	
	wp_enqueue_style('opt-out-generator-admin-style', plugin_dir_url(__FILE__) . 'assets/admin.css');
}
add_action('admin_enqueue_scripts', 'opt_out_generator_enqueue_admin_scripts');

function opt_out_generator_options_section() {}

function opt_out_generator_shortcode_render($args) {
	echo '<code>[opt_out_generator ' . $args['processId'] . ']</code>';
}

function opt_out_generator_name_render($args) {
    echo '<input name="opt_out_generator_name_' . $args['processId'] . '" type="text" value="' . esc_attr($args['processName']) . '"  />
    <p>Der Name des Verfahrens wird lediglich im Admin-Bereich verwendet, um das Verfahren identifizieren zu können.</p>';
}

function opt_out_generator_counter_render($args) {
	echo '<code>' . get_option('opt_out_generator_counter_' . $args['processId'], 0) . '</code>';
}

function opt_out_generator_threshold_render($args) {
	echo '<input name="opt_out_generator_threshold_' . $args['processId'] . '" type="text" value="' . get_option('opt_out_generator_threshold_' . $args['processId'], 0) . '"  />';
}

function opt_out_generator_info_text_render($args) {
    wp_editor(get_option('opt_out_generator_info_text_' . $args['processId']), 'opt_out_generator_info_text', array( 
        'textarea_name' => 'opt_out_generator_info_text_' . $args['processId'],
        'media_buttons' => false,
    ));
}

function opt_out_generator_info_button_render($args) {
	echo '<input name="opt_out_generator_info_button_' . $args['processId'] . '" type="text" value="' . get_option('opt_out_generator_info_button_' . $args['processId']) . '"  />';
}

function opt_out_generator_form_button_render($args) {
	echo '<input name="opt_out_generator_form_button_' . $args['processId'] . '" type="text" value="' . get_option('opt_out_generator_form_button_' . $args['processId']) . '"  />';
}

function opt_out_generator_mail_subject_render($args) {
	echo '<input name="opt_out_generator_mail_subject_' . $args['processId'] . '" type="text" value="' . get_option('opt_out_generator_mail_subject_' . $args['processId']) . '"  style="width: 100%;"/>';
}

function opt_out_generator_mail_text_render($args) {
    wp_editor(get_option('opt_out_generator_mail_text_' . $args['processId']), 'opt_out_generator_mail_text', array( 
        'textarea_name' => 'opt_out_generator_mail_text_' . $args['processId'],
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

function opt_out_generator_good_bye_text_render($args) {
    wp_editor(get_option('opt_out_generator_good_bye_text_' . $args['processId']), 'opt_out_generator_good_bye_text', array( 
        'textarea_name' => 'opt_out_generator_good_bye_text_' . $args['processId'],
        'media_buttons' => false,
    ));
}

function opt_out_generator_settings_link($links) {
	array_unshift(
		$links,
		'<a href="' . esc_url(opt_out_generator_get_options_url()) . '">Einstellungen</a>'
	);
	return $links;
}

add_filter('plugin_action_links_opt-out-generator/opt-out-generator.php', 'opt_out_generator_settings_link');