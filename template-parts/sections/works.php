<?php
/**
 * Section: Works grid / sc-examples carousel (miauto_work CPT).
 *
 * Accepted $args:
 *   'layout'      => 'grid' (default) | 'sc-examples'
 *   'post_id'     => int   Service post ID (used with sc-examples layout)
 *   'skip_styles' => bool  Skip enqueueing works CSS
 *
 * @package miauto
 */

$layout     = ! empty( $args['layout'] ) ? $args['layout'] : 'grid';
$service_id = ! empty( $args['post_id'] ) ? (int) $args['post_id'] : 0;

// ── Enqueue assets ──────────────────────────────────────────────────

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-works' );
}
wp_enqueue_script( 'miauto-works' );

// ── Build query ─────────────────────────────────────────────────────

$query_args = array(
    'post_type'      => 'miauto_work',
    'posts_per_page' => 12,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

if ( 'sc-examples' === $layout && $service_id ) {
    $query_args['meta_query'] = array(
        array(
            'key'     => '_miauto_work_services',
            'value'   => 'post:miauto_service:' . $service_id,
            'compare' => 'LIKE',
        ),
    );
}

$works = new WP_Query( $query_args );

if ( ! $works->have_posts() ) {
    return;
}

$card_count = $works->post_count;

// ── Section title ───────────────────────────────────────────────────

if ( 'sc-examples' === $layout && $service_id ) {
    $section_title = miauto_get_meta( 'miauto_sc_examples_title', $service_id );
    if ( empty( $section_title ) ) {
        $section_title = 'Примеры наших работ';
    }
} else {
    $section_title = 'Наши работы';
}

// ── Render ──────────────────────────────────────────────────────────

if ( 'sc-examples' === $layout ) : ?>

<section class="sc-examples" aria-label="<?php echo esc_attr( $section_title ); ?>" data-count="<?php echo esc_attr( $card_count ); ?>">
    <div class="container">

        <div class="sc-examples__header">
            <h2 class="sc-examples__title"><?php echo esc_html( $section_title ); ?></h2>
            <div class="sc-examples__nav">
                <button class="sc-examples__nav-btn -prev" type="button" aria-label="Назад">
                    <svg viewBox="0 0 10 10"><path d="M7 2L3 5L7 8"/></svg>
                </button>
                <button class="sc-examples__nav-btn -next" type="button" aria-label="Вперёд">
                    <svg viewBox="0 0 10 10"><path d="M3 2L7 5L3 8"/></svg>
                </button>
            </div>
        </div>

        <div class="sc-examples__viewport">
            <div class="sc-examples__track">

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
                                        <li><?php echo esc_html( $item['defect_text'] ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $done ) ) : ?>
                                <div class="works__card-section -repairs">
                                    <span class="works__card-section-title">Выполненные работы</span>
                                    <ul class="works__card-list">
                                        <?php foreach ( $done as $item ) : ?>
                                        <li><?php echo esc_html( $item['done_text'] ); ?></li>
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

        <div class="sc-examples__dots">
            <?php for ( $i = 0; $i < $card_count; $i++ ) : ?>
            <button class="sc-examples__dot<?php echo 0 === $i ? ' -active' : ''; ?>" type="button" aria-label="<?php echo esc_attr( 'Карточка ' . ( $i + 1 ) ); ?>"></button>
            <?php endfor; ?>
        </div>

    </div>
</section><!-- /.sc-examples -->

<?php else : ?>

<section class="works" aria-label="<?php echo esc_attr( $section_title ); ?>">
    <div class="container">

        <h1 class="works__title"><?php echo esc_html( $section_title ); ?></h1>

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
                                    <li><?php echo esc_html( $item['defect_text'] ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $done ) ) : ?>
                            <div class="works__card-section -repairs">
                                <span class="works__card-section-title">Выполненные работы</span>
                                <ul class="works__card-list">
                                    <?php foreach ( $done as $item ) : ?>
                                    <li><?php echo esc_html( $item['done_text'] ); ?></li>
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

<?php endif; ?>
