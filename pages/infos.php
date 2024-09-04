<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$processes = get_option('opt_out_generator_processes', []);
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

?>
<div class="opt-out-generator infos">
	<?php
	
	echo do_shortcode(wpautop($process['info_text']));

	if($process['counter'] >= $process['threshold']) {
		if($process['counter'] == 1) {
			echo '<p>Es wurde bereits eine Datenabfrage erstellt.</p>';
		} else {
			echo '<p>Es wurden bereits ' . $process['counter'] . ' Datenabfragen erstellt.</p>';
		}
	}
    
	?>

    <div class="actions">
	    <input class="form button" type="button" value="<?=esc_attr($process['info_button'])?>" />
    </div>
</div>