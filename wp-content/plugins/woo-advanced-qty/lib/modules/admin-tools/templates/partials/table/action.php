<?php

use MTTWordPressTheme\Lib\Abstracts\Model;

/**
 * @var $action_hook
 * @var Model $item
 * @var string $table_name
 * @var string $slug
 */
$args = [
    'action' => $action_hook !== '' ? $action_hook : $action
];

$args[$slug] = $item->id;

if(!empty($page)){
    $args['page'] = $page;
}

$href =  wp_nonce_url(add_query_arg($args, html_entity_decode(menu_page_url($page, false))), 'row_action_' . $table_name,'row_action_' . $table_name);
?>
<a href="<?php echo $href; ?>" data-item-id="<?php $item->id; ?>" data-action="<?php echo ($action_hook !== '' ? $action_hook : $action); ?>"<?php echo isset($class) ? ' class="' . $class . '"' : ''?>><?php echo $title ?></a>