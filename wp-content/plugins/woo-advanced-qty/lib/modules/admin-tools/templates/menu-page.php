<?php
	$page_actions = apply_filters('mtt/admin/page/' . $page->menu_slug . '/actions', array());
?>
<div class="wrap">
	<h1<?php echo !empty($page_actions) ? ' class="wp-heading-inline"' : ''; ?>><?php echo $page->getPageTitle(); ?></h1>
	<?php
		foreach((array) $page_actions as $action) {
			$label = $action['label'];
			$href = isset($action['href']) ? $action['href'] : null;
			$classes = implode(' ', $action['classes']);
			if (empty($href) && isset($action['action'])) {
				$args = array(
					'page' => $menu_page_slug,
					'action' => $action['action'],
				);
				$href = get_admin_url() .'?'. http_build_query($args);
			}

			echo "<a href='$href' class='mtt-admin-page-button $classes'>$label</a>";
		}
	?>
	<hr class="wp-header-end">
	<?php
		\do_action('mtt/admin/page/' . $page->menu_slug . '/tabs', ['page' => $page]);
		\do_action('mtt/admin/page/' . $page->menu_slug . '/contentcallback', ['page' => $page]);
	?>
</div>