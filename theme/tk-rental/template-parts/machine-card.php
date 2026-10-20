<?php
/** 機械カード1件分。get_template_part( 'template-parts/machine-card', null, array( 'machine' => $row ) ) */
$m = isset( $args['machine'] ) ? $args['machine'] : null;
if ( ! $m ) {
	return;
}
$specs = array_slice( TKMR_DB::decode_specs( $m['specs'] ), 0, 3 );
$maker = trim( $m['maker'] . ( $m['maker'] && $m['model_year'] ? ' / ' : '' ) . ( $m['model_year'] ? $m['model_year'] . '年製' : '' ) );
?>
<article class="machine">
  <a class="machine-link" href="<?php echo esc_url( tkt_machine_url( $m['id'] ) ); ?>">
    <div class="machine-photo">
      <span class="status <?php echo esc_attr( tkt_status_class( $m['status'] ) ); ?>"><?php echo esc_html( tkt_status_label( $m['status'] ) ); ?></span>
      <?php echo tkt_machine_photo( $m, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
    </div>
    <div class="machine-body">
      <?php if ( $m['category'] ) : ?><p class="machine-cat"><?php echo esc_html( $m['category'] ); ?></p><?php endif; ?>
      <h3><?php echo esc_html( $m['name'] ); ?></h3>
      <?php if ( $maker ) : ?><p class="machine-maker"><?php echo esc_html( $maker ); ?></p><?php endif; ?>
      <?php if ( $specs ) : ?>
        <dl class="spec">
          <?php foreach ( $specs as $s ) : ?>
            <div><dt><?php echo esc_html( $s['label'] ); ?></dt><dd><?php echo esc_html( $s['value'] ); ?></dd></div>
          <?php endforeach; ?>
        </dl>
      <?php endif; ?>
      <p class="price"><small>月額</small><?php echo esc_html( tkt_price( $m['monthly_price'] ) ); ?><?php if ( (int) $m['monthly_price'] > 0 ) : ?><small>〜</small><?php endif; ?></p>
    </div>
  </a>
</article>
