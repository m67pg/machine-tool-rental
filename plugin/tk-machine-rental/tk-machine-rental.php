<?php
/**
 * Plugin Name: TK Machine Rental
 * Description: 工作機械レンタル用プラグイン。レンタル機械を専用テーブルで管理（一覧・登録・編集・削除）する管理画面を提供します。
 * Version:     1.0.0
 * Requires PHP: 7.4
 * Text Domain: tk-machine-rental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TKMR_VERSION', '1.0.0' );
define( 'TKMR_FILE', __FILE__ );
define( 'TKMR_DIR', plugin_dir_path( __FILE__ ) );
define( 'TKMR_URL', plugin_dir_url( __FILE__ ) );

require_once TKMR_DIR . 'includes/class-tkmr-db.php';
require_once TKMR_DIR . 'includes/class-tkmr-admin.php';

// 有効化時: テーブルがなければ作成、あれば何もしない
register_activation_hook( __FILE__, array( 'TKMR_DB', 'install' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( is_admin() ) {
			TKMR_Admin::init();
		}
	}
);
