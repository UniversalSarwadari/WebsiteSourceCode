<optgroup label="<?php echo esc_attr($label); ?>">
	<?php
		foreach($options as $key => $option) {
			?>
			<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($option); ?></option>
			<?php
		}
	?>
</optgroup>