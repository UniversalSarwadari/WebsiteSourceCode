<div id="<?php echo $notice['id']; ?>" class="notice <?php echo $notice['class'] . ($notice['is_dismissible'] ? ' is-dismissible' : '');?>">
	<p>
		<?php echo $notice['message']; ?>
	</p>
</div>