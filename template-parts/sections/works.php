<?php
/**
 * Section: Works grid (miauto_work CPT).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-works' );
}
wp_enqueue_script( 'miauto-works' );

$works = new WP_Query( array(
    'post_type'      => 'miauto_work',
    'posts_per_page' => 12,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

if ( ! $works->have_posts() ) {
    return;
}
?>

<section class="works" aria-label="Наши работы">
    <div class="container">

        <h1 class="works__title">Наши работы</h1>

        <div class="works__grid">
            <?php while ( $works->have_posts() ) : $works->the_post(); ?>
                <?php
                $work_id   = get_the_ID();
                $model     = miauto_get_meta( 'miauto_work_model', $work_id );
                $mileage   = miauto_get_meta( 'miauto_work_mileage', $work_id );
                $issue     = miauto_get_meta( 'miauto_work_issue', $work_id );
                $defects   = miauto_get_meta( 'miauto_work_defects', $work_id );
                $done      = miauto_get_meta( 'miauto_work_done', $work_id );
                $price     = miauto_get_meta( 'miauto_work_price', $work_id );
                $duration  = miauto_get_meta( 'miauto_work_duration', $work_id );
                $gallery   = miauto_get_meta( 'miauto_work_gallery', $work_id );
                ?>
                <article class="works__card">
                    <?php if ( ! empty( $gallery ) ) : ?>
                    <div class="works__carousel">
                        <div class="works__carousel-track">
                            <?php foreach ( $gallery as $img ) : ?>
                            <div class="works__carousel-slide">
                                <?php echo wp_get_attachment_image( $img, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( count( $gallery ) > 1 ) : ?>
                        <button class="works__carousel-btn -prev" type="button" aria-label="Предыдущее фото"><svg viewBox="0 0 12 12"><path d="M8 2L4 6L8 10"/></svg></button>
                        <button class="works__carousel-btn -next" type="button" aria-label="Следующее фото"><svg viewBox="0 0 12 12"><path d="M4 2L8 6L4 10"/></svg></button>
                        <div class="works__carousel-dots">
                            <?php for ( $i = 0; $i < count( $gallery ); $i++ ) : ?>
                            <button class="works__carousel-dot<?php echo 0 === $i ? ' -active' : ''; ?>" type="button" aria-label="<?php echo esc_attr( 'Фото ' . ( $i + 1 ) ); ?>"></button>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="works__card-content">
                        <div class="works__card-info">
                            <div class="works__card-header">
                                <div class="works__card-title-row">
                                    <?php if ( ! empty( $model ) ) : ?>
                                    <span class="works__card-model"><?php echo esc_html( $model ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $mileage ) ) : ?>
                                    <span class="works__card-mileage"><?php echo esc_html( $mileage ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ( ! empty( $issue ) ) : ?>
                                <span class="works__card-issue"><?php echo esc_html( $issue ); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ( ! empty( $defects ) ) : ?>
                            <div class="works__card-section">
                                <span class="works__card-section-title">Дефектовка</span>
                                <ul class="works__card-list">
                                    <?php foreach ( $defects as $item ) : ?>
                                    <li><?php echo esc_html( $item['text'] ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $done ) ) : ?>
                            <div class="works__card-section">
                                <span class="works__card-section-title">Выполненные работы</span>
                                <ul class="works__card-list">
                                    <?php foreach ( $done as $item ) : ?>
                                    <li><?php echo esc_html( $item['text'] ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="works__card-footer">
                            <?php if ( ! empty( $price ) ) : ?>
                            <div class="works__card-meta">
                                <span class="works__card-meta-label">Стоимость работ</span>
                                <span class="works__card-meta-value -price"><?php echo esc_html( $price ); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $duration ) ) : ?>
                            <div class="works__card-meta">
                                <span class="works__card-meta-label">Срок ремонта</span>
                                <span class="works__card-meta-value"><?php echo esc_html( $duration ); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>

    </div>
</section><!-- /.works -->
