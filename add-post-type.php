<?php
load_theme_textdomain('Coinmarketcap', get_template_directory() . '/languages');
function register_coin_post_type() {
    register_post_type('coin', // Register Custom Post Type
        array('labels' => array('name' => __('Cryptocurrency', 'Coinmarketcap'), // Rename these to suit
            'singular_name' => __('Coin', 'Coinmarketcap'), 'add_new' => __('Add New', 'wp'), 'add_new_item' => __('Add New Coin', 'Coinmarketcap'), 'edit' => __('Edit', 'wp'), 'edit_item' => __('Edit Cryptocurrency', 'Coinmarketcap'), 'new_item' => __('New Coin', 'Coinmarketcap'), 'view' => __('View Coin', 'Coinmarketcap'), 'view_item' => __('View Coin', 'Coinmarketcap'), 'search_items' => __('Search Coin', 'Coinmarketcap'), 'not_found' => __('No Cryptocurrency Posts found', 'Coinmarketcap'), 'not_found_in_trash' => __('No Cryptocurrency Posts found in Trash', 'Coinmarketcap')), 'public' => true, 'hierarchical' => false, // Allows your posts to behave like Hierarchy Pages
            'has_archive' => (get_field('coin_has_archive', 'coin_options')) ? : 'coin', 'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'), 'can_export' => true, // Allows export in Tools > Export
            'taxonomies' => array()));
    flush_rewrite_rules();
}
add_action('init', 'register_coin_post_type');
// Show or Hide Custom Fields on Edit Page
//add_filter('acf/settings/remove_wp_meta_box', '__return_false');
// Create config page
if (function_exists('acf_add_options_page')) {
    function updated_message() {
        $message = (get_transient('updated_message')) ? : 'bomb has been planted';
        delete_transient('updated_message');
        return $message;
    }
    acf_add_options_sub_page(array('parent' => 'edit.php?post_type=coin', 'page_title' => 'Cryptocurrency Options', 'menu_title' => 'Options', 'capability' => 'edit_posts', 'icon_url' => false, 'redirect' => true, 'post_id' => 'coin_options', 'autoload' => false, 'updated_message' => updated_message(),));
}
function pre_get_coins($query) {
    if (!is_admin() and $query->is_main_query() and is_post_type_archive('coin')) {
        $query->set('posts_per_page', get_field('coin_per_page', 'coin_options'));
        $query->set('meta_key', get_field('coin_meta_key', 'coin_options'));
        $query->set('orderby', get_field('coin_orderby', 'coin_options'));
        $query->set('order', get_field('coin_order', 'coin_options'));
        $query->set('post_status', 'publish');
    }
}
add_action('pre_get_posts', 'pre_get_coins');
function acf_load_select_meta_keys($field) {
    $field['choices'] = array();
    $meta_keys = get_option('coin_meta_keys');
    foreach ($meta_keys as $choice) {
        $field['choices'][$choice] = $choice;
    }
    return $field;
}
add_filter('acf/load_field/name=coin_meta_key', 'acf_load_select_meta_keys');
function add_option_field_to_general_admin_page() {
    register_setting('general', 'coin_meta_keys');
    add_settings_field('coin_meta_keys', '', 'callback_function', 'general', 'default', array('id' => 'coin_meta_keys', 'option_name' => 'coin_meta_keys', 'css' => 'hidden'));
    register_setting('general', 'coin_global');
    add_settings_field('coin_global', '', 'callback_function', 'general', 'default', array('id' => 'coin_global', 'option_name' => 'coin_global', 'css' => 'hidden'));
    function callback_function() {
        return false;
    }
}
add_action('admin_init', 'add_option_field_to_general_admin_page');
function before_acf_options_page() {
    ob_start();
}
add_action('coin_page_acf-options-options', 'before_acf_options_page', 1);
function after_acf_options_page($coinmarketcap) {
    $content = ob_get_clean();
    $count = 1;
    $coin_global = json_decode(get_option('coin_global'));
    $my_content = '<strong>Global JSON:</strong>';
    $my_content.= '<ul>';
    foreach ($coin_global as $key => $value) {
        $my_content.= '<li>' . $key . ': ' . $value . '</li>';
    }
    $my_content.= '</ul>';
    $content = str_replace('</form>', '</form>' . $my_content, $content, $count);
    echo $content;
}
add_action('coin_page_acf-options-options', 'after_acf_options_page', 20);
