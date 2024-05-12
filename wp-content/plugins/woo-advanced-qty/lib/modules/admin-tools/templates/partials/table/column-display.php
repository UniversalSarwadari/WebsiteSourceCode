<?php
	if(!empty($edit_url)) {
		\Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project::getTemplate('partials.link', array('url' => $edit_url, 'link_text' => $text), true, array('lib.modules.admin-tools.templates'));
	} else {
		echo $text;
	}