<?php
/**
 * レンタル機械テーブルの作成とCRUD。
 * テーブル名: {$wpdb->prefix}tk_machines （WordPress標準テーブルとは別）
 * テーマ側からも TKMR_DB::get( $id ) / TKMR_DB::query( $args ) で利用できます。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TKMR_DB {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'tk_machines';
	}

	/** カテゴリ一覧（フィルタで変更可能） */
	public static function categories() {
		return apply_filters(
			'tkmr_categories',
			array( '旋盤', 'フライス盤', 'マシニングセンタ', '研削盤', '溶接機' )
		);
	}

	/** 状況一覧 */
	public static function statuses() {
		return array(
			'available'   => '空きあり',
			'reserved'    => '予約中',
			'unavailable' => '貸出不可',
		);
	}

	/**
	 * 有効化時に呼ばれる。テーブルが既にあれば何もしない。
	 */
	public static function install() {
		global $wpdb;

		$table  = self::table();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( $exists === $table ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  category varchar(100) NOT NULL DEFAULT '',
  maker varchar(100) NOT NULL DEFAULT '',
  model_year smallint(5) unsigned DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'available',
  monthly_price int(10) unsigned NOT NULL DEFAULT 0,
  image_id bigint(20) unsigned NOT NULL DEFAULT 0,
  description longtext,
  accessories text,
  specs longtext,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY category (category),
  KEY status (status)
) {$charset};";

		dbDelta( $sql );
	}

	/** 1件取得。なければ null */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', absint( $id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/** 登録。新しいIDを返す（失敗時 false） */
	public static function insert( array $data ) {
		global $wpdb;
		$now  = current_time( 'mysql' );
		$data = array_merge( $data, array( 'created_at' => $now, 'updated_at' => $now ) );
		$ok   = $wpdb->insert( self::table(), $data, self::formats( $data ) );
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/** 更新 */
	public static function update( $id, array $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		$ok = $wpdb->update( self::table(), $data, array( 'id' => absint( $id ) ), self::formats( $data ), array( '%d' ) );
		return false !== $ok;
	}

	/** 削除 */
	public static function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * 一覧取得。
	 * $args: search, category, status, orderby, order, per_page, paged
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$args = self::normalize_args( $args );
		list( $where, $params ) = self::build_where( $args );

		$sql      = 'SELECT * FROM ' . self::table() . " WHERE {$where} ORDER BY {$args['orderby']} {$args['order']}, id DESC LIMIT %d OFFSET %d";
		$params[] = $args['per_page'];
		$params[] = ( $args['paged'] - 1 ) * $args['per_page'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	/** 条件に一致する総件数 */
	public static function count( array $args = array() ) {
		global $wpdb;
		$args = self::normalize_args( $args );
		list( $where, $params ) = self::build_where( $args );

		$sql = 'SELECT COUNT(*) FROM ' . self::table() . " WHERE {$where}";
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params );
		}
		return (int) $wpdb->get_var( $sql );
	}

	/** specs(JSON) を [ ['label'=>..,'value'=>..], ... ] に戻す */
	public static function decode_specs( $json ) {
		$list = json_decode( (string) $json, true );
		return is_array( $list ) ? $list : array();
	}

	// ------------------------------------------------------------------

	private static function normalize_args( array $args ) {
		$defaults = array(
			'search'   => '',
			'category' => '',
			'status'   => '',
			'orderby'  => 'updated_at',
			'order'    => 'DESC',
			'per_page' => 20,
			'paged'    => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$allowed = array( 'id', 'name', 'category', 'status', 'monthly_price', 'updated_at', 'created_at' );
		if ( ! in_array( $args['orderby'], $allowed, true ) ) {
			$args['orderby'] = 'updated_at';
		}
		$args['order']    = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
		$args['per_page'] = max( 1, absint( $args['per_page'] ) );
		$args['paged']    = max( 1, absint( $args['paged'] ) );
		return $args;
	}

	private static function build_where( array $args ) {
		global $wpdb;
		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(name LIKE %s OR maker LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		if ( '' !== $args['category'] ) {
			$where[]  = 'category = %s';
			$params[] = $args['category'];
		}
		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		return array( implode( ' AND ', $where ), $params );
	}

	private static function formats( array $data ) {
		$map = array(
			'monthly_price' => '%d',
			'model_year'    => '%d',
			'image_id'      => '%d',
		);
		$formats = array();
		foreach ( array_keys( $data ) as $key ) {
			$formats[] = isset( $map[ $key ] ) ? $map[ $key ] : '%s';
		}
		return $formats;
	}
}
