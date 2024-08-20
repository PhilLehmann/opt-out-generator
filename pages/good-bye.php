<?php

defined('ABSPATH') or die('');
defined('OPT_OUT_GENERATOR_PROCESS_ID') or die('');

?>
<div class="opt-out-generator good-bye">
	<?php
        echo wpautop(get_option('opt_out_generator_good_bye_text_' . OPT_OUT_GENERATOR_PROCESS_ID));
    ?>
    
	<div class="actions">
        <?php
            if($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo opt_out_generator_get_post_form('action="?gp=result"', '<input class="submit button" type="button" value="&lt; Zurück" />');
            }
        ?>
    </div>
</div>