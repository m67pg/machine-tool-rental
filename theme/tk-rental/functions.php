<?php
/**
 * TK Rental テーマ
 * 機械データはプラグイン「TK Machine Rental」（TKMR_DB）から取得します。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TKT_VERSION', '1.0.0' );

/** 会社情報（ダミー）。差し替えは下の値を直接変更するか、tkt_company フィルターで。 */
function tkt_company() {
	return apply_filters(
		'tkt_company',
		array(
			'name'      => '東和機工',
			'suffix'    => 'レンタル',
			'tel'       => '03-1234-5678',
			'tel_hours' => '平日 9:00–18:00',
			'email'     => 'info@example.com',
			'zip'       => '〒100-0000',
			'address'   => '東京都○○区○○ 1-2-3',
			'license'   => '古物商許可 第000000000000号',
		)
	);
}

function tkt_machines_ready() {
	return class_exists( 'TKMR_DB' );
}

function tkt_machines_url( $args = array() ) {
	$url = home_url( '/machines/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function tkt_machine_url( $id ) {
	return home_url( '/machines/' . (int) $id . '/' );
}

function tkt_price( $n ) {
	return (int) $n > 0 ? '¥' . number_format( (int) $n ) : '要相談';
}

function tkt_status_class( $status ) {
	$map = array( 'available' => 'status-ok', 'reserved' => 'status-wait' );
	return isset( $map[ $status ] ) ? $map[ $status ] : 'status-ng';
}

function tkt_status_label( $status ) {
	$labels = tkt_machines_ready() ? TKMR_DB::statuses() : array();
	return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
}

/** 写真（未設定ならプレースホルダー） */
function tkt_machine_photo( $m, $size = 'large' ) {
	if ( ! empty( $m['image_id'] ) ) {
		$img = wp_get_attachment_image( (int) $m['image_id'], $size, false, array( 'alt' => $m['name'] ) );
		if ( $img ) {
			return $img;
		}
	}
	return '<span class="ph">機械写真</span>';
}

function tkt_mailto( $subject = '' ) {
	$c   = tkt_company();
	$url = 'mailto:' . $c['email'];
	if ( $subject ) {
		$url .= '?subject=' . rawurlencode( $subject );
	}
	return $url;
}

function tkt_plugin_notice() {
	echo '<div class="empty"><p>機械データを表示するには、プラグイン「TK Machine Rental」を有効化してください。</p></div>';
}

// ---------------------------------------------------------------------
// テーマ設定・アセット
// ---------------------------------------------------------------------

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'tkt-fonts',
			'https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@500;700;900&family=Noto+Sans+JP:wght@400;500&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'tkt-style', get_stylesheet_uri(), array( 'tkt-fonts' ), TKT_VERSION );
	}
);

// ---------------------------------------------------------------------
// URL設計: /machines/ （一覧）, /machines/page/2/, /machines/{ID}/ （詳細）
// ---------------------------------------------------------------------

function tkt_register_rewrites() {
	add_rewrite_rule( '^machines/page/([0-9]+)/?$', 'index.php?tkt_view=archive&paged=$matches[1]', 'top' );
	add_rewrite_rule( '^machines/?$', 'index.php?tkt_view=archive', 'top' );
	add_rewrite_rule( '^machines/([0-9]+)/?$', 'index.php?tkt_view=single&tkt_machine_id=$matches[1]', 'top' );
}
add_action( 'init', 'tkt_register_rewrites' );

// テーマ切り替え時にルールを反映
add_action(
	'after_switch_theme',
	function () {
		tkt_register_rewrites();
		flush_rewrite_rules();
	}
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'tkt_view';
		$vars[] = 'tkt_machine_id';
		return $vars;
	}
);

// 独自URLを404/正規化リダイレクトの対象外にする
add_filter(
	'pre_handle_404',
	function ( $preempt ) {
		return get_query_var( 'tkt_view' ) ? true : $preempt;
	}
);
add_filter(
	'redirect_canonical',
	function ( $redirect ) {
		return get_query_var( 'tkt_view' ) ? false : $redirect;
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		$view = get_query_var( 'tkt_view' );
		if ( ! $view ) {
			return $template;
		}

		if ( 'archive' === $view ) {
			$found = locate_template( 'templates/machine-archive.php' );
			return $found ? $found : $template;
		}

		if ( 'single' === $view ) {
			$machine = tkt_machines_ready() ? TKMR_DB::get( (int) get_query_var( 'tkt_machine_id' ) ) : null;
			if ( ! $machine ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				return get_404_template();
			}
			$GLOBALS['tkt_machine'] = $machine;
			$found                  = locate_template( 'templates/machine-single.php' );
			return $found ? $found : $template;
		}
		return $template;
	}
);

// <title>
add_filter(
	'pre_get_document_title',
	function ( $title ) {
		$view = get_query_var( 'tkt_view' );
		if ( 'archive' === $view ) {
			return 'レンタル機械一覧 | ' . get_bloginfo( 'name' );
		}
		if ( 'single' === $view && ! empty( $GLOBALS['tkt_machine'] ) ) {
			$m = $GLOBALS['tkt_machine'];
			return trim( $m['category'] . ' ' . $m['name'] ) . ' | ' . get_bloginfo( 'name' );
		}
		return $title;
	}
);

// 管理バー表示時の固定ヘッダー位置
add_action(
	'wp_head',
	function () {
		if ( is_admin_bar_showing() ) {
			echo '<style>.site-header{top:32px}@media(max-width:782px){.site-header{top:46px}}</style>';
		}
	}
);
