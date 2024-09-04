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

function opt_out_generator_get_mail_text($params) {
	if(!isset($params['gp_name']) || !isset($params['gp_strasse']) || !isset($params['gp_plz']) || !isset($params['gp_ort']) || !isset($params['gp_kasse']) || !isset($params['gp_nummer'])) {
		wp_die('Einer der Parameter "gp_name", "gp_strasse", "gp_plz", "gp_ort", "gp_kasse", "gp_nummer" fehlt.');
	}
	if($params['gp_kasse'] == 'other' && !isset($params['gp_kk_name'])) {
		wp_die('Der Parameter "gp_kk_name" fehlt.');
	}
	
	$tokens = [];
	$tokens['[name]'] = esc_html($params['gp_name']);
	$tokens['[strasse]'] = esc_html($params['gp_strasse']);
	$tokens['[plz]'] = esc_html($params['gp_plz']);
	$tokens['[ort]'] = esc_html($params['gp_ort']);
	if($params['gp_kasse'] == 'other') {
		$tokens['[kasse]'] = esc_html($params['gp_kk_name']);
	} else {
		$tokens['[kasse]'] = esc_html($params['gp_kasse']);
	}
	$tokens['[versichertennummer]'] = esc_html($params['gp_nummer']);

    $processes = get_option('opt_out_generator_processes', []);
    $mail_text = $processes[OPT_OUT_GENERATOR_PROCESS_ID]['mail_text'];
	return str_replace(array_keys($tokens), array_values($tokens), $mail_text);
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

add_action('rest_api_init', 'opt_out_generator_api');

// Admin section

function opt_out_generator_init_admin_frontend() {
    global $pagenow;
    if(($pagenow !== 'options-general.php' || $_GET['page'] !== 'opt_out_generator_options') && 
       ($pagenow !== 'options.php' || $_POST['option_page'] !== 'opt_out_generator_options_section')) {
        return;
    }
    
    $processes = get_option('opt_out_generator_processes', []);
    $processId = opt_out_generator_get_process();

    if($processId === '') {
        return;
    }

    // Configure options screen for a specific process

    $process = $processes[$processId];
    $args = [
        'processes' => $processes,
        'processId' => $processId,
        'process' => $process
    ];

    add_settings_section('opt_out_generator_options_section', 'Opt-Out-Verfahren <u>' . $process['name'] . '</u>: ', 'opt_out_generator_options_section', 'opt_out_generator_options');
    add_settings_field('opt_out_generator_shortcode', 'Shortcode', 'opt_out_generator_shortcode_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_name', 'Name des Opt-Out-Verfahrens', 'opt_out_generator_name_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_counter', 'Stand des Abfragen-Zählers', 'opt_out_generator_counter_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_threshold', 'Schwellwert für die Anzeige des Abfragen-Zählers', 'opt_out_generator_threshold_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_info_text', 'Text der "Info"-Seite', 'opt_out_generator_info_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_info_button', 'Button-Text auf der "Info"-Seite', 'opt_out_generator_info_button_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_form_button', 'Button-Text auf der Formular-Seite', 'opt_out_generator_form_button_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_mail_subject', 'Betreff der versendeten Nachricht', 'opt_out_generator_mail_subject_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_mail_text', 'Text der versendeten Nachricht', 'opt_out_generator_mail_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_send_mail', 'Nachricht als Mail versenden?', 'opt_out_generator_send_mail_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_good_bye_text', 'Text der "Weitere Infos"-Seite', 'opt_out_generator_good_bye_text_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
    add_settings_field('opt_out_generator_krankenkassen_hinweise', 'Krankenkassenspezifische Hinweise', 'opt_out_generator_krankenkassen_hinweise_render', 'opt_out_generator_options', 'opt_out_generator_options_section', $args);
} 

function opt_out_generator_init_admin_create_process() {
    global $pagenow;
    if($pagenow !== 'options-general.php' || $_GET['page'] !== 'opt_out_generator_options' || !isset($_GET['action']) || $_GET['action'] !== 'create_process') {
        return;
    }

    $processes = get_option('opt_out_generator_processes', []);
    $count = array_push($processes, [
        'name' => 'Opt-Out-Verfahren',
        'counter' => 0,
        'threshold' => 0,
        'info_text' => 'Lorem Ipsum.',
        'info_button' => 'Frage deine Daten ab!',
        'form_button' => 'Anfrage erstellen',
        'mail_subject' => 'Lorem Ipsum.',
        'mail_text' => 'Lorem Ipsum.',
        'send_mail' => 0,
        'krankenkassen_hinweise' => [],
        'good_bye_text' => 'Lorem Ipsum.'
    ]);
    update_option('opt_out_generator_processes', $processes);

    $processId = $count - 1;
    wp_redirect(opt_out_generator_get_options_url([ 'process' => $processId ]));
    exit;
}

function opt_out_generator_init_admin_delete_process() {
    global $pagenow;
    if($pagenow !== 'options-general.php' || $_GET['page'] !== 'opt_out_generator_options' || !isset($_GET['action']) || $_GET['action'] !== 'delete_process') {
        return;
    }
    
    $processes = get_option('opt_out_generator_processes', []);
    $processId = opt_out_generator_get_process();

    if($processId === '') {
        return;
    }

    array_splice($processes, $processId, 1);
    update_option('opt_out_generator_processes', $processes);

    wp_redirect(opt_out_generator_get_options_url());
    exit;
}

function opt_out_generator_init_admin_export_processes() {
    global $pagenow;
    if($pagenow !== 'options-general.php' || $_GET['page'] !== 'opt_out_generator_options' || !isset($_GET['action']) || $_GET['action'] !== 'export_processes') {
        return;
    }
    
    $processes = get_option('opt_out_generator_processes', []);

    header('Content-Disposition: attachment; filename="opt-out-generator-export.json"');
    echo json_encode($processes, JSON_PRETTY_PRINT);
    exit;
}

function opt_out_generator_init_admin_import_processes() {
    global $pagenow;
    if($pagenow !== 'options-general.php' || $_GET['page'] !== 'opt_out_generator_options' || !isset($_GET['action']) || $_GET['action'] !== 'import_processes' || 
      !isset($_FILES['import-file']) || $_FILES['import-file']['error'] != UPLOAD_ERR_OK || !is_uploaded_file($_FILES['import-file']['tmp_name'])) {
        return;
    }
    
    $processes = get_option('opt_out_generator_processes', []);

    $fileContents = file_get_contents($_FILES['import-file']['tmp_name']);
    $newProcesses = json_decode($fileContents, true);

    array_push($processes, ...$newProcesses);
    update_option('opt_out_generator_processes', $processes);

    $processId = opt_out_generator_get_process();

    if($processId === '') {
        wp_redirect(opt_out_generator_get_options_url());
    } else {
        wp_redirect(opt_out_generator_get_options_url([ 'process' => $processId ]));
    }
    exit;
}

function opt_out_generator_init_admin_backend() {
    global $pagenow;
    if($pagenow !== 'options.php' || $_POST['option_page'] !== 'opt_out_generator_options_section') {
        return;
    }

    $processes = get_option('opt_out_generator_processes', []);
    $processId = opt_out_generator_get_process();

    if($processId === '') {
        return;
    }

    // Store options, redirect
    // Note: we do not use register_setting here, as we'd like to store the options in a JSON construct for several reasons

    foreach(['name', 'info_text', 'info_button', 'form_button', 'mail_subject', 'mail_text', 'good_bye_text'] as $property) {
        if(isset($_POST['opt_out_generator_' . $property])) {
            $processes[$processId][$property] = $_POST['opt_out_generator_' . $property];
        }
    }

    foreach(['threshold', 'send_mail'] as $property) {
        if(isset($_POST['opt_out_generator_' . $property]) && ctype_digit($_POST['opt_out_generator_' . $property])) {
            $processes[$processId][$property] = intval($_POST['opt_out_generator_' . $property]);
        } else {
            $processes[$processId][$property] = 0;
        }
    }

    if(isset($_POST['opt_out_generator_krankenkassen_hinweise']) && is_array($_POST['opt_out_generator_krankenkassen_hinweise'])) {
        foreach($_POST['opt_out_generator_krankenkassen_hinweise'] as $name => $hinweis) {
            if(trim($hinweis) === '') {
                if(isset($processes[$processId]['krankenkassen_hinweise'][$name])) {
                    unset($processes[$processId]['krankenkassen_hinweise'][$name]);
                }
            } else {
                $processes[$processId]['krankenkassen_hinweise'][$name] = $hinweis;
            }
        }
    }

    update_option('opt_out_generator_processes', $processes);

    wp_redirect(opt_out_generator_get_options_url([ 'process' => $processId ]));
    exit;
} 
add_action('admin_init', 'opt_out_generator_init_admin_create_process');
add_action('admin_init', 'opt_out_generator_init_admin_delete_process');
add_action('admin_init', 'opt_out_generator_init_admin_export_processes');
add_action('admin_init', 'opt_out_generator_init_admin_import_processes');
add_action('admin_init', 'opt_out_generator_init_admin_frontend');
add_action('admin_init', 'opt_out_generator_init_admin_backend');

function opt_out_generator_add_admin_menu() {
	add_options_page('Einstellungen › opt-out-generator', 'opt-out-generator', 'manage_options', 'opt_out_generator_options', 'opt_out_generator_options_page');
}
add_action('admin_menu', 'opt_out_generator_add_admin_menu');

function opt_out_generator_options_page() {

    $processes = get_option('opt_out_generator_processes', []);
    $processId = opt_out_generator_get_process();
    
?><div class="wrap">
	<h1>Einstellungen › opt-out-generator</h1>
    <h2>Liste der Opt-Out-Verfahren</h2>

    <form action="<?=esc_url(opt_out_generator_get_options_url([ 'process' => $processId, 'action' => 'import_processes' ]))?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />
        <input id="import-file-input" name="import-file" type="file" onchange="form.submit()" accept="application/JSON" class="hidden" />
    </form>

    <form action="options-general.php" method="get">
        <input type="hidden" name="page" value="opt_out_generator_options" />
        <table class="form-table processes wp-filter" role="presentation">
            <tbody>
                <tr>
                    <td><?php
        
        if(count($processes) > 0) {
            echo '<select id="opt_out_generator_process" name="process">';
            if($processId !== '') {
                $selectedIndex = $processId;
            } else {
                $selectedIndex = -1;
                echo '<option value="" selected="selected">Bitte wähle einen Prozess aus</option>';
            }
            foreach($processes as $index => $process) {
                echo '<option value="' . $index . '" ' . ($selectedIndex == $index ? 'selected="selected"' : '') . '>' . $process['name'] . '</option>';
            }
            echo "</select>";
        }

        if($processId !== '') {
            echo '<a href="' . esc_url(opt_out_generator_get_options_url([ 'process' => $processId, 'action' => 'delete_process' ])) . '" class="button button-primary button-danger"><span class="dashicons dashicons-trash"></span> Verfahren löschen</a>';
        }

        echo '<a href="' . esc_url(opt_out_generator_get_options_url([ 'action' => 'create_process' ])) . '" class="button button-primary"><span class="dashicons dashicons-plus-alt"></span> Neues Verfahren hinzufügen</a>';

        if(count($processes) > 0) {
            echo '<a href="' . esc_url(opt_out_generator_get_options_url([ 'action' => 'export_processes' ])) . '" class="button button-primary"><span class="dashicons dashicons-database-export"></span> Alle Verfahren exportieren</a>';
        }

        echo '<a class="button button-primary button-import"><span class="dashicons dashicons-database-import"></span> Verfahren importieren</a>';
        
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

function opt_out_generator_enqueue_admin_scripts($hook) {
    if($hook !== 'settings_page_opt_out_generator_options') {
        return;
    }
	wp_enqueue_script('jquery');
	wp_enqueue_script('select2-script', plugin_dir_url(__FILE__) . 'assets/select2.min.js', array('jquery'));
	wp_enqueue_style('select2-style', plugin_dir_url(__FILE__) . 'assets/select2.min.css');
	wp_enqueue_script('opt-out-generator-admin-script', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'));	
	wp_enqueue_style('opt-out-generator-admin-style', plugin_dir_url(__FILE__) . 'assets/admin.css');
}
add_action('admin_enqueue_scripts', 'opt_out_generator_enqueue_admin_scripts');

function opt_out_generator_options_section() {}

function opt_out_generator_shortcode_render($args) {
	echo '<code>[opt_out_generator ' . $args['processId'] . ']</code>';
}

function opt_out_generator_name_render($args) {
    echo '<input name="opt_out_generator_name" type="text" value="' . esc_attr($args['process']['name']) . '"  />
    <p>Der Name des Verfahrens wird lediglich im Admin-Bereich verwendet, um das Verfahren identifizieren zu können.</p>';
}

function opt_out_generator_counter_render($args) {
	echo '<code>' . esc_html($args['process']['counter']) . '</code>';
}

function opt_out_generator_threshold_render($args) {
	echo '<input name="opt_out_generator_threshold" type="text" value="' . esc_attr($args['process']['threshold']) . '"  />';
}

function opt_out_generator_info_text_render($args) {
    wp_editor($args['process']['info_text'], 'opt_out_generator_info_text', array( 
        'textarea_name' => 'opt_out_generator_info_text',
        'media_buttons' => false,
    ));
    echo '<p>Dieses Textfeld unterstützt <a href="https://codex.wordpress.org/Shortcode" target="_blank">Shortcodes</a>.</p>';
}

function opt_out_generator_info_button_render($args) {
	echo '<input name="opt_out_generator_info_button" type="text" value="' . esc_attr($args['process']['info_button']) . '"  />';
}

function opt_out_generator_form_button_render($args) {
	echo '<input name="opt_out_generator_form_button" type="text" value="' . esc_attr($args['process']['form_button']) . '"  />';
}

function opt_out_generator_mail_subject_render($args) {
	echo '<input name="opt_out_generator_mail_subject" type="text" value="' . esc_attr($args['process']['mail_subject']) . '"  style="width: 100%;"/>';
}

function opt_out_generator_mail_text_render($args) {
    wp_editor($args['process']['mail_text'], 'opt_out_generator_mail_text', array( 
        'textarea_name' => 'opt_out_generator_mail_text',
        'media_buttons' => false,
    ));
?>

<p>
	Dieses Textfeld unterstützt Platzhalter, zum Beispiel <code>[name]</code> und <code>[versichertennummer]</code>, um vom User eingegebene Daten in die Mitteilung zu übernehmen.
	
	Verfügbare Variablen: 
	<ul>
		<li><code>[name]</code> für den kompletten Namen des Versicherten</li>
		<li><code>[strasse]</code> für die Straße des Versicherten</li>
		<li><code>[plz]</code> für die Postleitzahl des Versicherten</li>
		<li><code>[ort]</code> für den Ort des Versicherten</li>
		<li><code>[kasse]</code> für die Krankenkasse des Versicherten</li>
		<li><code>[versichertennummer]</code> für die Versichertennummer</li>
	</ul>
</p>

<?php
}

function opt_out_generator_send_mail_render($args) {
    echo '<input type="checkbox" id="opt_out_generator_send_mail" name="opt_out_generator_send_mail" value="1"' . checked(1, $args['process']['send_mail'], false) . '/>' .
    '<label for="opt_out_generator_send_mail">Dies ermöglicht den Versand der Opt-Out-Nachricht per Mail. Ist diese Option deaktiviert, ist lediglich der Download einer PDF möglich. ' . 
    'Dies ist die Standardeinstellung für neue Opt-Out-Verfahren, da Krankenkassen unserer Erfahrung nach auf nicht-elektronische Nachrichten besser reagieren.</label>';
}

function opt_out_generator_good_bye_text_render($args) {
    wp_editor($args['process']['good_bye_text'], 'opt_out_generator_good_bye_text', array( 
        'textarea_name' => 'opt_out_generator_good_bye_text',
        'media_buttons' => false,
    ));
    echo '<p>Dieses Textfeld unterstützt <a href="https://codex.wordpress.org/Shortcode" target="_blank">Shortcodes</a>.</p>';
}

function opt_out_generator_krankenkassen_hinweise_render($args) {
    ?>
    <select class="select2 krankenkasse">
        <option></option>
        <?php
            $hinweise = $args['process']['krankenkassen_hinweise'];
            $krankenkassenMitHinweisen = array_keys(array_filter($hinweise));
            require_once __DIR__ . '/includes/krankenkassen.php';
            $opt_out_generator_krankenkassen = opt_out_generator_Krankenkassenliste::getInstance();
            $opt_out_generator_krankenkassen->printOptions($krankenkassenMitHinweisen);
        ?>
        <option value="other">Andere...</option>
    </select>
    <a class="link show-all">Alle Hinweise anzeigen</a>
    <a class="link show-none hidden">Alle Hinweise einklappen</a>
<?php
    $names = $opt_out_generator_krankenkassen->getNames();
    foreach($names as $index => $name) {
        echo '<div data-name="' . $name . '" class="krankenkassen-hinweis hidden"><b class="krankenkassen-name hidden">' . $name . '</b>';
        wp_editor(isset($hinweise[$name]) ? $hinweise[$name] : '', 'opt_out_generator_krankenkassen_hinweise_' . $index, array( 
            'textarea_name' => 'opt_out_generator_krankenkassen_hinweise[' . $name . ']',
            'media_buttons' => false,
            'editor_height' => 250
        ));
        echo '</div>';
    }
}

function opt_out_generator_settings_link($links) {
	array_unshift(
		$links,
		'<a href="' . esc_url(opt_out_generator_get_options_url()) . '">Einstellungen</a>'
	);
	return $links;
}

add_filter('plugin_action_links_opt-out-generator/opt-out-generator.php', 'opt_out_generator_settings_link');