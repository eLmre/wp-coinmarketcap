<?php
function every_minutes($schedules) {
    $schedules['every_five_minutes'] = array('interval' => 5 * 60, 'display' => __('Every 5 Minutes', 'ohayo'));
    $schedules['every_ten_minutes'] = array('interval' => 10 * 60, 'display' => __('Every 10 Minutes', 'ohayo'));
    return $schedules;
}
add_filter('cron_schedules', 'every_minutes');
function wp_register_theme_activation_hook($code, $function) {
    $optionKey = "theme_is_activated_" . $code;
    if (!get_option($optionKey)) {
        call_user_func($function);
        update_option($optionKey, 1);
    }
}
function wp_register_theme_deactivation_hook($code, $function) {
    $GLOBALS["wp_register_theme_deactivation_hook_function" . $code] = $function;
    $fn = create_function('$theme', ' call_user_func($GLOBALS["wp_register_theme_deactivation_hook_function' . $code . '"]); delete_option("theme_is_activated_' . $code . '");');
    add_action("switch_theme", $fn);
}
// Activate cron task when theme active
function theme_activate() {
    wp_clear_scheduled_hook('update_currency_event');
    $timestamp = (get_field('coin_cron_time', 'coin_options')) ? : 'hourly';
    wp_schedule_event(time(), $timestamp, 'update_currency_event');
}
wp_register_theme_activation_hook('ohayo', 'theme_activate');
// Also we need to check options for time change
function check_cron_time_changes($value, $post_id, $field) {
    $coin_cron_time = get_field('coin_cron_time', $post_id);
    if ($value != $coin_cron_time) {
        theme_activate();
        set_transient('updated_message', 'All options was saved And New time for Cron task was established: ' . $value . '', 5);
    }
    return $value;
}
add_filter('acf/update_value/name=coin_cron_time', 'check_cron_time_changes', 10, 3);
// Remove cron task when theme deactivated
function theme_deactivate() {
    wp_clear_scheduled_hook('update_currency_event');
}
wp_register_theme_deactivation_hook('ohayo', 'theme_deactivate');
if (defined('DOING_CRON') && DOING_CRON) {
    add_action('update_currency_event', 'start_update');
    function start_update() {
        $coinmarketcap = new Coinmarketcap();
        $coinmarketcap->ticker();
    }
}
