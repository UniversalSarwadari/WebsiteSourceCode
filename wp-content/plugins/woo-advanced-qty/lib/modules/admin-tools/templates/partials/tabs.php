<h2 class="nav-tab-wrapper">
	<?php
		foreach($tabs as $tab) {
			?>
				<a href="?page=<?php echo $page->menu_slug . ($tab['slug'] === $primary_tab ? '' : '&tab=' . $tab['slug']); ?>" class="nav-tab <?php echo $active_tab === $tab['slug'] ? 'nav-tab-active' : ''; ?>"><?php echo $tab['title']; ?></a>
			<?php
		}
	?>
</h2>