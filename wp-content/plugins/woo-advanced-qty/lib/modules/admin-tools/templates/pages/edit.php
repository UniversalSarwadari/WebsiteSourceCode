<?php

/**
 * @var Model $instance
 * @var string $type The model type / slug
 */

?>
<form name="edit-<?php echo $type; ?>" method="post" id="form-<?php echo $type; ?>" enctype="application/x-www-form-urlencoded">
    <?php wp_nonce_field( 'edit_' . $type , "_nonce_$type"); ?>
    <input type="hidden" name="<?php echo $type; ?>[id]" value="<?php echo $instance->ID; ?>">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
	        <div id="post-body-content">
		        <?php
			        do_meta_boxes(null, 'normal', $instance);
			        do_meta_boxes(null, 'advanced', $instance);
			        // ACF
			        do_action('mtt/admin/page/edit/content/after', $instance, $type) ?>
	        </div>
	        <div id="postbox-container-1" class="postbox-container">
		        <?php do_meta_boxes(null, 'side', $instance); ?>
		        <?php do_action('mtt/admin/page/edit/sidebar/after', $instance, $type) ?>
	        </div>
        </div>
    </div>
</form>
