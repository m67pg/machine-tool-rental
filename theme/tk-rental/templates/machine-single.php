<?php
/** 機械詳細（/machines/{ID}/） */
get_header();

$m     = $GLOBALS['tkt_machine'];
$specs = TKMR_DB::decode_specs( $m['specs'] );
$acc   = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $m['accessories'] ) ) );

$related = array();
if ( '' !== $m['category'] ) {
	$rows    = TKMR_DB::query( array( 'category' => $m['category'], 'per_page' => 4 ) );
	$related = array_slice(
		array_filter(
			$rows,
			function ( $r ) use ( $m ) {
				return (int) $r['id'] !== (int) $m['id'];
			}
		),
		0,
		3
	);
}
?>
<div class="wrap">
  <nav class="breadcrumb" aria-label="パンくず">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">トップ</a>
    <a href="<?php echo esc_url( tkt_machines_url() ); ?>">機械一覧</a>
    <?php if ( $m['category'] ) : ?><a href="<?php echo esc_url( tkt_machines_url( array( 'category' => $m['category'] ) ) ); ?>"><?php echo esc_html( $m['category'] ); ?></a><?php endif; ?>
    <span aria-current="page"><?php echo esc_html( $m['name'] ); ?></span>
  </nav>
</div>

<section class="detail">
  <div class="wrap detail-inner">

    <div class="detail-gallery">
      <div class="detail-photo">
        <span class="status <?php echo esc_attr( tkt_status_class( $m['status'] ) ); ?>"><?php echo esc_html( tkt_status_label( $m['status'] ) ); ?></span>
        <?php echo tkt_machine_photo( $m, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      </div>
    </div>

    <div class="detail-summary">
      <?php if ( $m['category'] ) : ?><p class="machine-cat"><?php echo esc_html( $m['category'] ); ?></p><?php endif; ?>
      <h1><?php echo esc_html( $m['name'] ); ?></h1>
      <?php if ( $m['maker'] || $m['model_year'] ) : ?>
        <p class="machine-maker"><?php echo esc_html( trim( $m['maker'] . ( $m['maker'] && $m['model_year'] ? ' / ' : '' ) . ( $m['model_year'] ? $m['model_year'] . '年製' : '' ) ) ); ?></p>
      <?php endif; ?>

      <div class="detail-price">
        <p class="price price-lg"><small>月額</small><?php echo esc_html( tkt_price( $m['monthly_price'] ) ); ?><?php if ( (int) $m['monthly_price'] > 0 ) : ?><small>〜</small><?php endif; ?></p>
        <p class="note">税別。貸出期間・搬入先により変動します。</p>
      </div>

      <dl class="spec spec-sm">
        <div><dt>貸出状況</dt><dd><?php echo esc_html( tkt_status_label( $m['status'] ) ); ?></dd></div>
        <div><dt>設置場所</dt><dd>関東一円に搬入可</dd></div>
        <div><dt>貸出期間</dt><dd>1週間〜</dd></div>
      </dl>

      <div class="detail-actions">
        <a class="btn btn-primary" href="<?php echo esc_url( tkt_mailto( '【見積もり依頼】' . $m['name'] ) ); ?>">この機械の見積もりを依頼</a>
        <?php $c = tkt_company(); ?>
        <a class="btn btn-ghost" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $c['tel'] ) ); ?>">電話で相談</a>
      </div>
    </div>
  </div>
</section>

<?php if ( trim( $m['description'] ) || $acc || $specs ) : ?>
<section class="section section-tint">
  <div class="wrap detail-body">
    <div>
      <?php if ( trim( $m['description'] ) ) : ?>
        <h2 class="h-sub">機械の特徴</h2>
        <?php echo wp_kses_post( wpautop( $m['description'] ) ); ?>
      <?php endif; ?>

      <?php if ( $acc ) : ?>
        <h2 class="h-sub">付属品</h2>
        <ul class="plain-list">
          <?php foreach ( $acc as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ( $specs ) : ?>
    <div>
      <h2 class="h-sub">仕様</h2>
      <table class="spec-table">
        <tbody>
          <?php foreach ( $specs as $s ) : ?>
            <tr><th><?php echo esc_html( $s['label'] ); ?></th><td><?php echo esc_html( $s['value'] ); ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( $related ) : ?>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2>同じカテゴリの機械</h2></div>
    <div class="machine-grid">
      <?php foreach ( $related as $r ) { get_template_part( 'template-parts/machine-card', null, array( 'machine' => $r ) ); } ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="wrap"><p class="back"><a class="btn btn-ghost" href="<?php echo esc_url( tkt_machines_url() ); ?>">機械一覧に戻る</a></p></div>
<?php get_footer(); ?>
