<?php
/** 機械一覧（/machines/）: カテゴリ・空き状況の絞り込みとページ送り */
get_header();

if ( ! tkt_machines_ready() ) {
	echo '<section class="section"><div class="wrap">';
	tkt_plugin_notice();
	echo '</div></section>';
	get_footer();
	return;
}

$per_page = (int) apply_filters( 'tkt_machines_per_page', 12 );
$paged    = max( 1, (int) get_query_var( 'paged' ) );

$cat = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
if ( ! in_array( $cat, TKMR_DB::categories(), true ) ) {
	$cat = '';
}
$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
if ( ! isset( TKMR_DB::statuses()[ $status ] ) ) {
	$status = '';
}

$args  = array(
	'category' => $cat,
	'status'   => $status,
	'orderby'  => 'updated_at',
	'order'    => 'DESC',
	'per_page' => $per_page,
	'paged'    => $paged,
);
$items = TKMR_DB::query( $args );
$total = TKMR_DB::count( $args );
$pages = (int) ceil( $total / $per_page );

$base = tkt_machines_url();
?>
<div class="wrap">
  <nav class="breadcrumb" aria-label="パンくず">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">トップ</a><span aria-current="page">機械一覧</span>
  </nav>
</div>

<section class="section list-page">
  <div class="wrap">
    <div class="section-head">
      <h1 class="h-page">レンタル機械一覧</h1>
      <p>全<?php echo esc_html( number_format_i18n( $total ) ); ?>件<?php echo $cat ? '（' . esc_html( $cat ) . '）' : ''; ?></p>
    </div>

    <ul class="filter" aria-label="カテゴリで絞り込み">
      <li><a class="<?php echo '' === $cat ? 'is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_filter( array( 'status' => $status ) ), $base ) ); ?>">すべて</a></li>
      <?php foreach ( TKMR_DB::categories() as $c ) : ?>
        <li><a class="<?php echo $c === $cat ? 'is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_filter( array( 'category' => $c, 'status' => $status ) ), $base ) ); ?>"><?php echo esc_html( $c ); ?></a></li>
      <?php endforeach; ?>
    </ul>

    <p class="filter-sub">
      <?php if ( 'available' === $status ) : ?>
        <a href="<?php echo esc_url( add_query_arg( array_filter( array( 'category' => $cat ) ), $base ) ); ?>">空きのみ表示を解除</a>
      <?php else : ?>
        <a href="<?php echo esc_url( add_query_arg( array_filter( array( 'category' => $cat, 'status' => 'available' ) ), $base ) ); ?>">空きありのみ表示</a>
      <?php endif; ?>
    </p>

    <?php if ( $items ) : ?>
      <div class="machine-grid">
        <?php foreach ( $items as $m ) { get_template_part( 'template-parts/machine-card', null, array( 'machine' => $m ) ); } ?>
      </div>

      <?php if ( $pages > 1 ) : ?>
        <nav class="pager" aria-label="ページ送り">
          <?php
          echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
            array(
              'base'      => home_url( '/machines/page/%#%/' ),
              'format'    => '',
              'current'   => $paged,
              'total'     => $pages,
              'add_args'  => array_filter( array( 'category' => $cat, 'status' => $status ) ),
              'prev_text' => '前へ',
              'next_text' => '次へ',
              'type'      => 'plain',
            )
          );
          ?>
        </nav>
      <?php endif; ?>
    <?php else : ?>
      <p class="empty">該当する機械はありません。</p>
    <?php endif; ?>
  </div>
</section>
<?php get_footer(); ?>
