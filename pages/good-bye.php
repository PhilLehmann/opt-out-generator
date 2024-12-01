<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

$processes = opt_out_generator_get_processes();
$process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

?>
<div class="opt-out-generator good-bye">
	<?=do_shortcode(wpautop($process['good_bye_text']))?>
    
	<div class="actions">
        <?php
            if($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo opt_out_generator_get_post_form('action="?gp=result' . (isset($_GET['tp']) ? '&tp=1' : '') . '"', '<input class="submit button" type="button" value="&lt; Zurück" />');
            }
        ?>
    </div>
</div>