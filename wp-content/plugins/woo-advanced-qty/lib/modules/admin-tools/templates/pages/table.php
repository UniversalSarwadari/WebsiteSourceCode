<?php
/**
 * @var string $table_slug
 * @var string $menu_page_slug
 * @var string $add_new_label
 */

	use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\AdminTable;

?>
<form method="get" class="mtt-admin-table">
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	<?php
		do_action('mtt/admin/page/table/' . $table_slug . '/before', $table_slug, $menu_page_slug);
		AdminTable::displayTable($table_slug);
		do_action('mtt/admin/page/table/' . $table_slug . '/after', $table_slug, $menu_page_slug);
	?>
</form>

