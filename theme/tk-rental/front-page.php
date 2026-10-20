<?php
/** トップページ */
get_header();
$c        = tkt_company();
$latest   = tkt_machines_ready() ? TKMR_DB::query( array( 'per_page' => 6, 'orderby' => 'updated_at', 'order' => 'DESC' ) ) : array();
$tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $c['tel'] );
?>

<!-- ヒーロー -->
<section class="hero">
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <h1>買わずに、使う。<br>工作機械を必要な期間だけ。</h1>
      <p>旋盤、フライス盤、マシニングセンタまで。試作や短納期の案件、設備の入れ替え期間に、整備済みの機械を最短1週間からお貸しします。</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?php echo esc_url( tkt_machines_url() ); ?>">機械を探す</a>
        <a class="btn btn-ghost" href="#contact">相談する</a>
      </div>
    </div>

    <aside class="plate" aria-label="サービス概要">
      <p class="plate-title">RENTAL SPEC</p>
      <dl>
        <div><dt>取扱機種</dt><dd>旋盤 / フライス盤 / MC / 研削盤 / 溶接機</dd></div>
        <div><dt>貸出期間</dt><dd>1週間〜（長期割引あり）</dd></div>
        <div><dt>搬入設置</dt><dd>関東一円 立会い設置</dd></div>
        <div><dt>整備</dt><dd>貸出前に精度検査・点検済み</dd></div>
        <div><dt>保守</dt><dd>故障時は代替機を手配</dd></div>
      </dl>
    </aside>
  </div>
</section>

<!-- 機械一覧（新着6件） -->
<section class="section" id="machines">
  <div class="wrap">
    <div class="section-head">
      <h2>レンタル機械一覧</h2>
      <p>空き状況は毎日更新しています。</p>
    </div>

    <?php if ( tkt_machines_ready() ) : ?>
      <ul class="filter" aria-label="カテゴリで絞り込み">
        <li><a class="is-current" href="<?php echo esc_url( tkt_machines_url() ); ?>">すべて</a></li>
        <?php foreach ( TKMR_DB::categories() as $cat ) : ?>
          <li><a href="<?php echo esc_url( tkt_machines_url( array( 'category' => $cat ) ) ); ?>"><?php echo esc_html( $cat ); ?></a></li>
        <?php endforeach; ?>
      </ul>

      <?php if ( $latest ) : ?>
        <div class="machine-grid">
          <?php foreach ( $latest as $m ) { get_template_part( 'template-parts/machine-card', null, array( 'machine' => $m ) ); } ?>
        </div>
        <p class="more"><a class="btn btn-ghost" href="<?php echo esc_url( tkt_machines_url() ); ?>">機械をすべて見る</a></p>
      <?php else : ?>
        <p class="empty">現在掲載中の機械はありません。</p>
      <?php endif; ?>
    <?php else : ?>
      <?php tkt_plugin_notice(); ?>
    <?php endif; ?>
  </div>
</section>

<!-- 選ばれる理由 -->
<section class="section section-tint" id="strengths">
  <div class="wrap">
    <div class="section-head"><h2>選ばれる理由</h2></div>
    <div class="strengths">
      <div>
        <h3>1週間から借りられる</h3>
        <p>試作や納期が重なった時期だけ。設備投資を抱えずに受注できます。</p>
      </div>
      <div>
        <h3>貸出前に精度を検査</h3>
        <p>全機種、貸出のたびに精度検査と消耗部品の交換を行います。</p>
      </div>
      <div>
        <h3>搬入から設置まで一括</h3>
        <p>専任スタッフが立ち会い、水平出しと試運転までご対応します。</p>
      </div>
    </div>
  </div>
</section>

<!-- ご利用の流れ -->
<section class="section" id="flow">
  <div class="wrap">
    <div class="section-head"><h2>ご利用の流れ</h2></div>
    <ol class="flow">
      <li><h3>機械を選ぶ</h3><p>一覧から希望の機械と期間を確認します。</p></li>
      <li><h3>見積もり依頼</h3><p>メールか電話で連絡。1営業日以内に回答します。</p></li>
      <li><h3>契約・搬入</h3><p>契約後、日程を調整して搬入・設置します。</p></li>
      <li><h3>返却</h3><p>期間終了後に引き取りに伺います。</p></li>
    </ol>
  </div>
</section>

<!-- よくある質問 -->
<section class="section section-tint" id="faq">
  <div class="wrap narrow">
    <div class="section-head"><h2>よくある質問</h2></div>
    <details>
      <summary>最短の貸出期間はどれくらいですか？</summary>
      <p>1週間からご利用いただけます。機種により最短期間が異なる場合があります。</p>
    </details>
    <details>
      <summary>工具や治具も借りられますか？</summary>
      <p>バイトやエンドミルなど基本工具のセット貸しが可能です。見積もり時にご相談ください。</p>
    </details>
    <details>
      <summary>配送エリアを教えてください。</summary>
      <p>関東一円に対応しています。それ以外の地域もお問い合わせください。</p>
    </details>
    <details>
      <summary>操作や段取りの講習はありますか？</summary>
      <p>初回設置時に操作説明を行います。有償で技術者の派遣も承ります。</p>
    </details>
  </div>
</section>

<!-- お問い合わせ -->
<section class="contact" id="contact">
  <div class="wrap contact-inner">
    <div>
      <h2>機械の空き状況・料金のご相談</h2>
      <p>お探しの加工内容と希望期間をお知らせください。最適な機械をご提案します。</p>
    </div>
    <div class="contact-actions">
      <a class="btn btn-light" href="<?php echo esc_url( tkt_mailto( '見積もり依頼' ) ); ?>">メールで見積もり依頼</a>
      <a class="contact-tel" href="<?php echo esc_url( $tel_href ); ?>"><?php echo esc_html( $c['tel'] ); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
