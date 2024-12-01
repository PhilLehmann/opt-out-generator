<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$processes = opt_out_generator_get_processes();
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

?>
<div class="opt-out-generator infos">
	<?php

    $tokens = isset($process['third_party']) && $process['third_party'] == 'combo' ? [
        '<div class="oog-third-party-selector"><a href="?gp=infos" ' . (isset($_GET['tp']) ? '' : 'class="selected"') . '>Für mich</a><a href="?gp=infos&tp=1" ' . (isset($_GET['tp']) ? 'class="selected"' : '') . '>Für eine andere Person</a></div>',
        '<div class="oog-third-party-own ' . (isset($_GET['tp']) ? 'hidden' : '') . '">',
        '</div>',
        '<div class="oog-third-party-other ' . (isset($_GET['tp']) ? '' : 'hidden') . '">',
        '</div>'
    ] : ['', '', '', '', ''];

	echo do_shortcode(str_replace([
        '[auswahlfeld]', 
        '[opt-out-selbst]', 
        '[/opt-out-selbst]', 
        '[opt-out-dritte]', 
        '[/opt-out-dritte]'
    ], $tokens, wpautop($process['info_text'])));

	if($process['counter'] >= $process['threshold']) {
        echo '<p>Dieses Verfahren wurde bereits ' . number_format($process['counter'], 0, ',', '.') . ' mal genutzt.</p>';
	}
    
	?>

    <div class="actions">
	    <input class="form button <?=isset($_GET['tp']) ? 'oog-third-party' : '' ?>" type="button" value="<?=esc_attr($process['info_button'])?>" />
    </div>
</div>