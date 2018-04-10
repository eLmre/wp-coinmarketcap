# WP-Coinmarketcap
A simple static PHP wrapper for public CoinMarketCap API methods. Using cron task and curl to retrieve data from coinmarketcap and writes them into wordpress.

### Requirements
* [PHP 5.4.0 or higher](http://www.php.net/)
* [WordPress](https://wordpress.org)
* [Advanced Custom Fields](https://www.advancedcustomfields.com/)

### Installation
Import into your ACF plugin, file settings from acf-export.json
Include external files in functions.php
```sh
require_once get_template_directory() .'/Coinmarketcap.php';
require_once get_template_directory() .'/cron-task.php';
require_once get_template_directory() .'/add-post-type.php';
```
Check the cron task list. theme_activate hook creates a task at the moment of activation your theme, also theme_deactivate hook remove cron task when theme deactivated.

### Usage
```sh
get_option('coin_meta_keys'); // Return list of available meta-keys
get_option('coin_meta_keys'); // Global JSON Data
```
