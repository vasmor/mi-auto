<?php
/**
 * Section: Work Process (step-by-step).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-work-process' );
}

$title    = miauto_get_option( 'miauto_work_process_title' );
$subtitle = miauto_get_option( 'miauto_work_process_subtitle' );
$steps    = miauto_get_option( 'miauto_work_process_steps' );

if ( empty( $steps ) ) {
    return;
}
?>

<section class="work-process" aria-label="<?php echo esc_attr( $title ); ?>">
    <div class="container">

        <div class="work-process__header">
            <?php if ( ! empty( $title ) ) : ?>
            <h2 class="work-process__title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>
            <?php if ( ! empty( $subtitle ) ) : ?>
            <p class="work-process__subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
        </div>

        <div class="work-process__grid">
            <?php foreach ( $steps as $step ) : ?>
            <div class="work-process__step">
                <?php if ( ! empty( $step['step_svg'] ) ) : ?>
                <div class="work-process__icon">
                    <?php echo miauto_kses_svg( $step['step_svg'] ); ?>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $step['step_title'] ) ) : ?>
                <h3 class="work-process__step-title"><?php echo esc_html( $step['step_title'] ); ?></h3>
                <?php endif; ?>

                <?php if ( ! empty( $step['step_text'] ) ) : ?>
                <p class="work-process__step-text"><?php echo esc_html( $step['step_text'] ); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section><!-- /.work-process -->
