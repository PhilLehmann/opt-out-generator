<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$counter = get_option('opt_out_generator_counter_' . OPT_OUT_GENERATOR_PROCESS_ID, 0);
$threshold = get_option('opt_out_generator_threshold_' . OPT_OUT_GENERATOR_PROCESS_ID, 0);

?>
<div class="opt-out-generator infos">
	<?php
	
	echo wpautop(get_option('opt_out_generator_info_text_' . OPT_OUT_GENERATOR_PROCESS_ID));

	if($counter >= $threshold) {
		if($counter == 1) {
			echo '<p>Es wurde bereits eine Datenabfrage erstellt.</p>';
		} else {
			echo '<p>Es wurden bereits ' . $counter . ' Datenabfragen erstellt.</p>';
		}
	}
    
	?>

    <div class="actions">
	    <input class="form button" type="button" value="<?=esc_attr(get_option('opt_out_generator_info_button_' . OPT_OUT_GENERATOR_PROCESS_ID))?>" />
    </div>
</div>