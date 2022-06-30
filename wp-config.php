<?php
 
define( 'DB_NAME', '%example_db_name%' );
define( 'DB_USER', '%example_db_user_name%' );
define( 'DB_PASSWORD', '%example_db_password%' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
 
$table_prefix = 'wp_';
 
define( 'WP_DEBUG', false );
 
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
 
require_once ABSPATH . 'wp-settings.php';
