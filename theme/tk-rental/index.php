<?php
/** 汎用テンプレート（固定ページ・投稿などのフォールバック） */
get_header();
?>
<section class="section">
  <div class="wrap narrow">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article class="entry">
        <h1><?php the_title(); ?></h1>
        <div class="entry-body"><?php the_content(); ?></div>
      </article>
    <?php endwhile; else : ?>
      <p class="empty">コンテンツが見つかりません。</p>
    <?php endif; ?>
  </div>
</section>
<?php get_footer(); ?>
