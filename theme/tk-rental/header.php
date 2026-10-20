<?php $c = tkt_company(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $c['name'] ); ?><span><?php echo esc_html( $c['suffix'] ); ?></span></a>
    <nav class="gnav" aria-label="メインメニュー">
      <a href="<?php echo esc_url( tkt_machines_url() ); ?>">機械一覧</a>
      <a href="<?php echo esc_url( home_url( '/#strengths' ) ); ?>">選ばれる理由</a>
      <a href="<?php echo esc_url( home_url( '/#flow' ) ); ?>">ご利用の流れ</a>
      <a href="<?php echo esc_url( home_url( '/#faq' ) ); ?>">よくある質問</a>
    </nav>
    <div class="header-cta">
      <a class="tel" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $c['tel'] ) ); ?>"><?php echo esc_html( $c['tel'] ); ?><small><?php echo esc_html( $c['tel_hours'] ); ?></small></a>
      <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">見積もり依頼</a>
    </div>
  </div>
</header>

<main>
