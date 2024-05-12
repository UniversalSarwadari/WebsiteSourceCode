<div class="submitbox" id="submitpost">
	<div id="minor-publishing">
		<?php echo do_action("mtt/admin/page/edit/$slug/submitbox/minor", $instance, $id); ?>
	</div>
	<div id="major-publishing-actions">
		<?php
			if($id > 0) {
				?>
				<div id="delete-action">
					<a class="submitdelete deletion mtt-admin-delete" href="<?php echo $delete_url; ?>"><?php echo $delete_label; ?></a>
				</div>
				<?php
			}
		?>
		<div id="publishing-action">
			<span class="spinner"></span>
            <?php if(!empty($save_label)) { ?>
			    <button type="submit" name="save_<?php echo $slug ?>" id="update" class="button button-primary button-large" value="<?php echo $id; ?>"><?php echo $save_label; ?></button>
            <?php } ?>
		</div>
		<div class="clear"></div>
	</div>
</div>