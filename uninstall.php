<?php 
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	echo "ACCESS FORBIDDEN";
    exit();
}
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "stock_tickers" );
delete_option('stock_ticker_per_category_stock_lists');
delete_option('stock_ticker_version');
delete_option('stock_ticker_version_text');
?>
