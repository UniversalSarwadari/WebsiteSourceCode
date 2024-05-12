<?php
	/**
	 * @var string $text
	 * @var string|array $class
	 */
?>
<span class="<?php echo implode(' ', (array) $class); ?>"><?php echo $text; ?></span>
