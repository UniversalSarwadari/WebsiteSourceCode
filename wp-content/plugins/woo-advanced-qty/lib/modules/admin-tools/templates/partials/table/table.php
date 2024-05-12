<form id="<?php echo $table_name . '_form'; ?>" method="post" action="<?php echo \admin_url('admin-post.php'); ?>">
	<?php
		echo $table;
	?>
</form>
<?php
	echo $inline_edit;
?>