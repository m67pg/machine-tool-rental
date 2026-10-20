<?php $c = tkt_company(); ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <p class="logo logo-foot"><?php echo esc_html( $c['name'] ); ?><span><?php echo esc_html( $c['suffix'] ); ?></span></p>
    <p><?php echo esc_html( $c['zip'] . ' ' . $c['address'] ); ?><br><?php echo esc_html( $c['license'] ); ?></p>
    <ul>
      <li><a href="#">会社概要</a></li>
      <li><a href="#">プライバシーポリシー</a></li>
      <li><a href="#">特定商取引法に基づく表記</a></li>
    </ul>
  </div>
  <p class="copy">© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $c['name'] . $c['suffix'] ); ?></p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
