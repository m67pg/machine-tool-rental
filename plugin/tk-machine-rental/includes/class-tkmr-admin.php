<?php
/**
 * 管理画面: 一覧 / 新規追加 / 編集 / 削除
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TKMR_Admin {

	const SLUG_LIST = 'tkmr-machines';
	const SLUG_EDIT = 'tkmr-machine-edit';

	private static $hooks = array();

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_tkmr_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_tkmr_delete', array( __CLASS__, 'handle_delete' ) );
	}

	/**
	 * 管理画面を使える権限。既定は 'read'（購読者を含むログイン済みの全ユーザー）。
	 * 絞り込みたい場合は tkmr_capability フィルターで 'manage_options' などに変更する。
	 */
	public static function cap() {
		return apply_filters( 'tkmr_capability', 'read' );
	}

	public static function menu() {
		$list = add_menu_page(
			'レンタル機械',
			'レンタル機械',
			self::cap(),
			self::SLUG_LIST,
			array( __CLASS__, 'render_list' ),
			'dashicons-admin-tools',
			26
		);
		add_submenu_page( self::SLUG_LIST, 'レンタル機械一覧', '機械一覧', self::cap(), self::SLUG_LIST, array( __CLASS__, 'render_list' ) );
		$edit = add_submenu_page( self::SLUG_LIST, '機械の登録・編集', '新規追加', self::cap(), self::SLUG_EDIT, array( __CLASS__, 'render_edit' ) );

		self::$hooks = array( $list, $edit );
		add_action( 'load-' . $list, array( __CLASS__, 'handle_bulk' ) );
	}

	public static function assets( $hook ) {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}
		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'tkmr-admin', TKMR_URL . 'assets/admin.css', array(), TKMR_VERSION );
		wp_enqueue_script( 'tkmr-admin', TKMR_URL . 'assets/admin.js', array( 'jquery' ), TKMR_VERSION, true );
		wp_localize_script(
			'tkmr-admin',
			'tkmr',
			array(
				'mediaTitle'    => '機械の写真を選択',
				'mediaButton'   => 'この写真を使う',
				'confirmDelete' => 'この機械を削除します。元に戻せません。よろしいですか？',
				'confirmBulk'   => '選択した機械を削除します。元に戻せません。よろしいですか？',
			)
		);
	}

	// ------------------------------------------------------------------
	// 一覧
	// ------------------------------------------------------------------

	public static function render_list() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( '権限がありません。' );
		}
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once TKMR_DIR . 'includes/class-tkmr-list-table.php';

		$table = new TKMR_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">レンタル機械</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_EDIT ) ); ?>" class="page-title-action">新規追加</a>
			<hr class="wp-header-end">
			<?php self::notices(); ?>

			<form method="get" id="tkmr-list-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG_LIST ); ?>">
				<?php
				$table->search_box( '機械名・メーカーを検索', 'tkmr' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/** 一括削除（一覧ページ読み込み時に処理） */
	public static function handle_bulk() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '-1';
		if ( '-1' === $action && isset( $_REQUEST['action2'] ) ) {
			$action = sanitize_key( $_REQUEST['action2'] );
		}
		if ( 'delete' !== $action || empty( $_REQUEST['ids'] ) ) {
			return;
		}
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( '権限がありません。' );
		}
		check_admin_referer( 'bulk-machines' );

		$count = 0;
		foreach ( array_map( 'absint', (array) $_REQUEST['ids'] ) as $id ) {
			if ( $id && TKMR_DB::delete( $id ) ) {
				$count++;
			}
		}
		self::redirect( self::SLUG_LIST, array( 'tkmr_msg' => 'deleted', 'tkmr_count' => $count ) );
	}

	// ------------------------------------------------------------------
	// 新規追加・編集フォーム
	// ------------------------------------------------------------------

	public static function render_edit() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( '権限がありません。' );
		}

		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$machine = $id ? TKMR_DB::get( $id ) : null;
		if ( $id && ! $machine ) {
			wp_die( '指定された機械が見つかりません。' );
		}

		$defaults = array(
			'name'          => '',
			'category'      => '',
			'maker'         => '',
			'model_year'    => '',
			'status'        => 'available',
			'monthly_price' => '',
			'image_id'      => 0,
			'description'   => '',
			'accessories'   => '',
			'specs'         => '[]',
		);
		$m      = $machine ? array_merge( $defaults, $machine ) : $defaults;
		$errors = array();

		// 入力エラーで戻ってきた場合は入力内容を復元
		$key   = 'tkmr_form_' . get_current_user_id();
		$saved = get_transient( $key );
		if ( $saved && isset( $_GET['tkmr_msg'] ) && 'error' === $_GET['tkmr_msg'] ) {
			$m      = array_merge( $m, $saved['data'] );
			$errors = $saved['errors'];
			delete_transient( $key );
		}

		$specs = TKMR_DB::decode_specs( $m['specs'] );
		if ( ! $specs ) {
			$specs = array( array(), array(), array() );
		}
		$image_url = $m['image_id'] ? wp_get_attachment_image_url( (int) $m['image_id'], 'medium' ) : '';
		?>
		<div class="wrap">
			<h1><?php echo $id ? '機械を編集' : '機械を新規追加'; ?></h1>
			<?php self::notices(); ?>
			<?php foreach ( $errors as $e ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $e ); ?></p></div>
			<?php endforeach; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tkmr_save">
				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'tkmr_save' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tkmr-name">機械名 <span class="required">*</span></label></th>
						<td><input type="text" id="tkmr-name" name="name" class="regular-text" value="<?php echo esc_attr( $m['name'] ); ?>" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-category">カテゴリ</label></th>
						<td>
							<select id="tkmr-category" name="category">
								<option value="">選択してください</option>
								<?php foreach ( TKMR_DB::categories() as $c ) : ?>
									<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $m['category'], $c ); ?>><?php echo esc_html( $c ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-maker">メーカー</label></th>
						<td><input type="text" id="tkmr-maker" name="maker" class="regular-text" value="<?php echo esc_attr( $m['maker'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-year">製造年</label></th>
						<td><input type="number" id="tkmr-year" name="model_year" class="small-text" min="1900" max="<?php echo esc_attr( (int) gmdate( 'Y' ) + 1 ); ?>" value="<?php echo esc_attr( $m['model_year'] ); ?>"> 年</td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-status">貸出状況</label></th>
						<td>
							<select id="tkmr-status" name="status">
								<?php foreach ( TKMR_DB::statuses() as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $m['status'], $k ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-price">月額料金（税別）</label></th>
						<td>¥ <input type="number" id="tkmr-price" name="monthly_price" class="regular-text" min="0" step="1000" value="<?php echo esc_attr( $m['monthly_price'] ); ?>"> 円〜</td>
					</tr>
					<tr>
						<th scope="row">写真</th>
						<td>
							<input type="hidden" id="tkmr-image-id" name="image_id" value="<?php echo esc_attr( (int) $m['image_id'] ); ?>">
							<div id="tkmr-image-preview"><?php if ( $image_url ) : ?><img src="<?php echo esc_url( $image_url ); ?>" alt=""><?php endif; ?></div>
							<?php if ( current_user_can( 'upload_files' ) ) : ?>
								<button type="button" class="button" id="tkmr-image-select">写真を選択</button>
								<button type="button" class="button-link-delete" id="tkmr-image-remove" <?php echo $image_url ? '' : 'style="display:none"'; ?>>写真を外す</button>
							<?php else : ?>
								<p class="description">写真の設定・変更には、メディアのアップロード権限が必要です。</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-desc">機械の特徴</label></th>
						<td><textarea id="tkmr-desc" name="description" rows="6" class="large-text"><?php echo esc_textarea( $m['description'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="tkmr-acc">付属品</label></th>
						<td>
							<textarea id="tkmr-acc" name="accessories" rows="4" class="large-text"><?php echo esc_textarea( $m['accessories'] ); ?></textarea>
							<p class="description">1行に1つずつ入力してください。</p>
						</td>
					</tr>
					<tr>
						<th scope="row">仕様</th>
						<td>
							<table class="tkmr-specs widefat striped">
								<thead><tr><th>項目</th><th>値</th><th></th></tr></thead>
								<tbody id="tkmr-spec-rows">
									<?php foreach ( $specs as $s ) { self::spec_row( $s ); } ?>
								</tbody>
							</table>
							<p><button type="button" class="button" id="tkmr-spec-add">仕様の行を追加</button></p>
							<script type="text/template" id="tkmr-spec-template"><?php self::spec_row( array() ); ?></script>
						</td>
					</tr>
				</table>

				<?php submit_button( $id ? '更新' : '登録' ); ?>
			</form>
		</div>
		<?php
	}

	private static function spec_row( $s ) {
		$label = isset( $s['label'] ) ? $s['label'] : '';
		$value = isset( $s['value'] ) ? $s['value'] : '';
		?>
		<tr>
			<td><input type="text" name="spec_label[]" class="regular-text" placeholder="例: 主軸回転数" value="<?php echo esc_attr( $label ); ?>"></td>
			<td><input type="text" name="spec_value[]" class="regular-text" placeholder="例: 12,000 min⁻¹" value="<?php echo esc_attr( $value ); ?>"></td>
			<td><button type="button" class="button-link-delete tkmr-spec-remove">削除</button></td>
		</tr>
		<?php
	}

	// ------------------------------------------------------------------
	// 保存・削除
	// ------------------------------------------------------------------

	public static function handle_save() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( '権限がありません。' );
		}
		check_admin_referer( 'tkmr_save' );

		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$errors = array();
		$data   = self::sanitize( wp_unslash( $_POST ), $errors );

		if ( $id && ! TKMR_DB::get( $id ) ) {
			wp_die( '指定された機械が見つかりません。' );
		}

		if ( $errors ) {
			set_transient( 'tkmr_form_' . get_current_user_id(), array( 'errors' => $errors, 'data' => $data ), 5 * MINUTE_IN_SECONDS );
			$args = array( 'tkmr_msg' => 'error' );
			if ( $id ) {
				$args['id'] = $id;
			}
			self::redirect( self::SLUG_EDIT, $args );
		}

		if ( $id ) {
			$ok = TKMR_DB::update( $id, $data );
		} else {
			$ok = (bool) TKMR_DB::insert( $data );
		}

		self::redirect( self::SLUG_LIST, array( 'tkmr_msg' => $ok ? 'saved' : 'failed' ) );
	}

	public static function handle_delete() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( '権限がありません。' );
		}
		check_admin_referer( 'tkmr_delete_' . $id );

		$ok = $id ? TKMR_DB::delete( $id ) : false;
		self::redirect( self::SLUG_LIST, array( 'tkmr_msg' => $ok ? 'deleted' : 'failed', 'tkmr_count' => 1 ) );
	}

	/** 入力値をサニタイズ・検証して DB 用の配列にする */
	private static function sanitize( array $p, array &$errors ) {
		$name = isset( $p['name'] ) ? sanitize_text_field( $p['name'] ) : '';
		if ( '' === $name ) {
			$errors[] = '機械名は必須です。';
		}

		$category = isset( $p['category'] ) ? sanitize_text_field( $p['category'] ) : '';
		if ( '' !== $category && ! in_array( $category, TKMR_DB::categories(), true ) ) {
			$errors[] = 'カテゴリが不正です。';
			$category = '';
		}

		$status = isset( $p['status'] ) ? sanitize_key( $p['status'] ) : 'available';
		if ( ! isset( TKMR_DB::statuses()[ $status ] ) ) {
			$status = 'available';
		}

		$year = null;
		if ( isset( $p['model_year'] ) && '' !== trim( $p['model_year'] ) ) {
			$y = absint( $p['model_year'] );
			if ( $y < 1900 || $y > (int) gmdate( 'Y' ) + 1 ) {
				$errors[] = '製造年が不正です。';
			} else {
				$year = $y;
			}
		}

		$price = isset( $p['monthly_price'] ) ? absint( $p['monthly_price'] ) : 0;

		$image_id = isset( $p['image_id'] ) ? absint( $p['image_id'] ) : 0;
		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		$labels = isset( $p['spec_label'] ) ? (array) $p['spec_label'] : array();
		$values = isset( $p['spec_value'] ) ? (array) $p['spec_value'] : array();
		$specs  = array();
		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field( $label );
			$value = isset( $values[ $i ] ) ? sanitize_text_field( $values[ $i ] ) : '';
			if ( '' === $label && '' === $value ) {
				continue;
			}
			$specs[] = array( 'label' => $label, 'value' => $value );
			if ( count( $specs ) >= 50 ) {
				break;
			}
		}

		return array(
			'name'          => $name,
			'category'      => $category,
			'maker'         => isset( $p['maker'] ) ? sanitize_text_field( $p['maker'] ) : '',
			'model_year'    => $year,
			'status'        => $status,
			'monthly_price' => $price,
			'image_id'      => $image_id,
			'description'   => isset( $p['description'] ) ? wp_kses_post( $p['description'] ) : '',
			'accessories'   => isset( $p['accessories'] ) ? sanitize_textarea_field( $p['accessories'] ) : '',
			'specs'         => wp_json_encode( $specs, JSON_UNESCAPED_UNICODE ),
		);
	}

	// ------------------------------------------------------------------

	private static function redirect( $page, array $args = array() ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function notices() {
		if ( empty( $_GET['tkmr_msg'] ) ) {
			return;
		}
		$msg   = sanitize_key( $_GET['tkmr_msg'] );
		$count = isset( $_GET['tkmr_count'] ) ? absint( $_GET['tkmr_count'] ) : 0;

		if ( 'saved' === $msg ) {
			$text = '保存しました。';
			$type = 'success';
		} elseif ( 'deleted' === $msg ) {
			$text = $count . '件削除しました。';
			$type = 'success';
		} elseif ( 'failed' === $msg ) {
			$text = '処理に失敗しました。';
			$type = 'error';
		} else {
			return; // error は入力エラーとしてフォーム側で表示
		}
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $text ) );
	}
}
