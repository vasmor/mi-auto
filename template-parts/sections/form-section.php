<?php
/**
 * Section: Form (appointment).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-form' );
}

$form_title = miauto_get_option( 'miauto_form_title' );
$form_bg    = miauto_get_option( 'miauto_form_bg' );
$cf7_id     = miauto_get_option( 'miauto_form_cf7_id' );
?>

<section class="form-section" aria-label="Запись на обслуживание">
    <div class="form-section__container">

        <?php
        if ( ! empty( $form_bg ) ) {
            echo wp_get_attachment_image( $form_bg, 'full', false, array(
                'class'   => 'form-section__bg',
                'loading' => 'lazy',
                'alt'     => '',
            ) );
        }
        ?>

        <?php if ( ! empty( $cf7_id ) && shortcode_exists( 'contact-form-7' ) ) : ?>

            <div class="form-section__form">
                <?php if ( ! empty( $form_title ) ) : ?>
                <h2 class="form-section__title"><?php echo esc_html( $form_title ); ?></h2>
                <?php endif; ?>
                <?php echo do_shortcode( '[contact-form-7 id="' . esc_attr( $cf7_id ) . '"]' ); ?>
            </div>

        <?php else : ?>

            <form class="form-section__form" action="#" method="post">
                <?php if ( ! empty( $form_title ) ) : ?>
                <h2 class="form-section__title"><?php echo esc_html( $form_title ); ?></h2>
                <?php endif; ?>

                <div class="form-section__content">
                    <div class="form-section__inputs">
                        <input class="form-section__input" type="text" name="name" placeholder="Введите имя" autocomplete="name">
                        <input class="form-section__input" type="tel" name="phone" placeholder="+7 (___) ___-__-__" autocomplete="tel">
                        <input class="form-section__input" type="email" name="email" placeholder="Введите e-mail" autocomplete="email">
                    </div>

                    <button class="form-section__btn" type="submit">Записаться</button>

                    <label class="form-section__privacy">
                        <input class="form-section__checkbox" type="checkbox" name="privacy" required>
                        <span class="form-section__privacy-text">Согласен с условиями <a class="form-section__privacy-link" href="<?php echo esc_url( miauto_get_option( 'miauto_footer_privacy_url' ) ); ?>">Политики конфиденциальности данных</a></span>
                    </label>
                </div>
            </form>

        <?php endif; ?>

    </div><!-- /.form-section__container -->
</section><!-- /.form-section -->
