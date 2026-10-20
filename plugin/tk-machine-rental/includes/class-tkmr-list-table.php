<?php
/**
 * レンタル機械の一覧テーブル（検索・絞り込み・並び替え・ページ送り・一括削除）
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TKMR_List_Table extends WP_List_Table {

	const PER_PAGE = 20;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'machine',
				'plural'   => 'machines', // 一括操作の nonce: bulk-machines
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox">',
			'image'         => '写真',
			'name'          => '機械名',
			'category'      => 'カテゴリ',
			'maker'         => 'メーカー / 製造年',
			'status'        => '貸出状況',
			'monthly_price' => '月額',
			'updated_at'    => '更新日',
		);
	}

	protected function get_sortable_columns() {
		return array(
			'name'          => array( 'name', false ),
			'category'      => array( 'category', false ),
			'status'        => array( 'status', false ),
			'monthly_price' => array( 'monthly_price', false ),
			'updated_at'    => array( 'updated_at', true ),
		);
	}

	protected function get_bulk_actions() {
		return array( 'delete' => '削除' );
	}

	protected function get_primary_column_name() {
		return 'name';
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d">', (int) $item['id'] );
	}

	protected function column_image( $item ) {
		$url = $item['image_id'] ? wp_get_attachment_image_url( (int) $item['image_id'], 'thumbnail' ) : '';
		return $url ? '<img class="tkmr-thumb" src="' . esc_url( $url ) . '" alt="">' : '<span class="tkmr-thumb tkmr-thumb-empty"></span>';
	}

	protected function column_name( $item ) {
		$edit = add_query_arg(
			array( 'page' => TKMR_Admin::SLUG_EDIT, 'id' => (int) $item['id'] ),
			admin_url( 'admin.php' )
		);
		$del = wp_nonce_url(
			admin_url( 'admin-post.php?action=tkmr_delete&id=' . (int) $item['id'] ),
			'tkmr_delete_' . (int) $item['id']
		);

		$actions = array(
			'edit'   => '<a href="' . esc_url( $edit ) . '">編集</a>',
			'delete' => '<a href="' . esc_url( $del ) . '" class="submitdelete tkmr-delete">削除</a>',
		);
		return '<strong><a class="row-title" href="' . esc_url( $edit ) . '">' . esc_html( $item['name'] ) . '</a></strong>' . $this->row_actions( $actions );
	}

	protected function column_maker( $item ) {
		$year = $item['model_year'] ? $item['model_year'] . '年製' : '';
		return esc_html( trim( $item['maker'] . ( $item['maker'] && $year ? ' / ' : '' ) . $year ) ) ?: '—';
	}

	protected function column_status( $item ) {
		$labels = TKMR_DB::statuses();
		$label  = isset( $labels[ $item['status'] ] ) ? $labels[ $item['status'] ] : $item['status'];
		return '<span class="tkmr-badge tkmr-badge-' . esc_attr( $item['status'] ) . '">' . esc_html( $label ) . '</span>';
	}

	protected function column_monthly_price( $item ) {
		return $item['monthly_price'] ? '¥' . number_format_i18n( (int) $item['monthly_price'] ) . '〜' : '—';
	}

	protected function column_updated_at( $item ) {
		return esc_html( mysql2date( 'Y/m/d H:i', $item['updated_at'] ) );
	}

	protected function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) && '' !== $item[ $column_name ] ? esc_html( $item[ $column_name ] ) : '—';
	}

	/** 一覧上部のカテゴリ・状況フィルタ */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$cat = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
		$st  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		?>
		<div class="alignleft actions">
			<select name="category">
				<option value="">すべてのカテゴリ</option>
				<?php foreach ( TKMR_DB::categories() as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat, $c ); ?>><?php echo esc_html( $c ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="status">
				<option value="">すべての状況</option>
				<?php foreach ( TKMR_DB::statuses() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $st, $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( '絞り込み', '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	public function no_items() {
		echo 'レンタル機械はまだ登録されていません。';
	}

	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'name' );

		$args = array(
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'category' => isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '',
			'status'   => isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '',
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'updated_at',
			'order'    => isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'desc',
			'per_page' => self::PER_PAGE,
			'paged'    => $this->get_pagenum(),
		);

		$this->items = TKMR_DB::query( $args );
		$this->set_pagination_args(
			array(
				'total_items' => TKMR_DB::count( $args ),
				'per_page'    => self::PER_PAGE,
			)
		);
	}
}
