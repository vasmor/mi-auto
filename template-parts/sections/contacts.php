<?php
/**
 * Section: Contacts.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-contacts' );
}

$title      = miauto_get_option( 'miauto_contacts_section_title' );
$decoration = miauto_get_option( 'miauto_contacts_decoration' );
$map_embed  = miauto_get_option( 'miauto_contacts_map_embed' );

$address = miauto_get_option( 'miauto_address' );
$hours   = miauto_get_option( 'miauto_hours' );
$phones  = miauto_get_option( 'miauto_phones' );
$email   = miauto_get_option( 'miauto_email' );
$tg      = miauto_get_option( 'miauto_telegram_url' );
$vk      = miauto_get_option( 'miauto_vk_url' );

$heading_tag = is_page_template( 'page-contacts.php' ) ? 'h1' : 'h2';
?>

<section class="contacts" aria-label="Наши контакты">
    <div class="container">

        <!-- Info card -->
        <div class="contacts__info">
            <div class="contacts__info-inner">
                <?php if ( ! empty( $title ) ) : ?>
                <<?php echo $heading_tag; ?> class="contacts__title"><?php echo esc_html( $title ); ?></<?php echo $heading_tag; ?>>
                <?php endif; ?>

                <div class="contacts__list">
                    <!-- Address -->
                    <div class="contacts__item">
                        <svg class="contacts__icon" viewBox="0 0 16 19" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2.343 2.305C3.844.829 5.878 0 8 0c2.122 0 4.157.829 5.657 2.305C15.157 3.781 16 5.783 16 7.87c0 2.087-.843 4.089-2.343 5.565L8 19l-5.657-5.565A7.89 7.89 0 010 7.87C0 5.783.843 3.781 2.343 2.305zM8 10.119a2.286 2.286 0 001.616-.659A2.249 2.249 0 0010.286 7.87 2.249 2.249 0 009.616 6.28 2.286 2.286 0 008 5.622c-.606 0-1.188.237-1.616.659A2.249 2.249 0 005.714 7.87c0 .596.241 1.168.67 1.59A2.286 2.286 0 008 10.119z" fill="#EA3323"/>
                        </svg>
                        <span class="contacts__text"><?php echo esc_html( $address ); ?></span>
                    </div>

                    <!-- Hours -->
                    <div class="contacts__item">
                        <svg class="contacts__icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8 0C3.582 0 0 3.582 0 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm3.692 9.231H8a.615.615 0 01-.615-.616V3.077a.615.615 0 111.23 0V8h3.077a.615.615 0 110 1.231z" fill="#EA3323"/>
                        </svg>
                        <span class="contacts__text"><?php echo esc_html( $hours ); ?></span>
                    </div>

                    <!-- Phones -->
                    <?php if ( ! empty( $phones ) ) : ?>
                    <div class="contacts__phones">
                        <?php foreach ( $phones as $phone ) : ?>
                        <div class="contacts__phone-row">
                            <svg class="contacts__icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.885.51A1.8 1.8 0 013.227.003c.494.031.966.271 1.268.67l1.795 2.306a1.544 1.544 0 01.315 1.494l-.547 2.19a.534.534 0 00.178.643l2.457 2.457a.534.534 0 00.644.178l2.189-.547a1.544 1.544 0 011.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702A16.1 16.1 0 014.568 11.43 16.1 16.1 0 01.148 4.421c-.362-1.03-.036-2.137.704-2.877L1.885.51z" fill="#EA3323"/>
                            </svg>
                            <a class="contacts__phone-text" href="tel:<?php echo esc_attr( $phone['raw'] ); ?>"><?php echo esc_html( $phone['number'] ); ?></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Email -->
                    <div class="contacts__item">
                        <svg class="contacts__icon" viewBox="0 0 16 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M14 0H2C.895.001.001.95 0 2.227v9.546C.001 13.05.895 14 2 14h12c1.105-.001 1.999-.95 2-2.227V2.227C15.999.95 15.105.001 14 0zm-.506 3.684L8.35 8.139a.493.493 0 01-.7 0L2.506 3.684a.55.55 0 01.702-.005L8 6.83l4.792-4.151a.55.55 0 01.702.005z" fill="#EA3323"/>
                        </svg>
                        <a class="contacts__text" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                    </div>

                    <!-- Socials -->
                    <div class="contacts__socials">
                        <?php if ( ! empty( $tg ) ) : ?>
                        <a class="contacts__social" href="<?php echo esc_url( $tg ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
                            <svg class="contacts__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.24-1.59.15-.15 2.71-2.48 2.76-2.69a.21.21 0 00-.05-.19c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if ( ! empty( $vk ) ) : ?>
                        <a class="contacts__social" href="<?php echo esc_url( $vk ); ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте">
                            <svg class="contacts__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.215 19.944c-1.962 0-2.875-.288-4.46-1.44C4.752 19.8 1.576 20.812 1.438 19.08c0-1.3-.287-2.398-.612-3.597C.44 14.006 0 12.36 0 9.977 0 4.283 4.652 0 10.163 0c5.516 0 9.838 4.494 9.838 10.03.018 5.449-4.36 9.885-9.786 9.914zM10.296 4.92c-2.684-.139-4.776 1.727-5.239 4.653-.382 2.422.296 5.372.874 5.526.277.067.974-.499 1.409-.936a4.51 4.51 0 002.426.868c2.78.135 5.157-1.991 5.344-4.782.108-2.796-2.033-5.164-4.814-5.324v-.005z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Decoration image -->
        <?php
        if ( ! empty( $decoration ) ) {
            echo wp_get_attachment_image( $decoration, 'medium', false, array(
                'class'   => 'contacts__decoration',
                'loading' => 'lazy',
                'alt'     => '',
            ) );
        }
        ?>

        <!-- Map -->
        <?php if ( ! empty( $map_embed ) ) : ?>
        <div class="contacts__map">
            <?php echo wp_kses( $map_embed, array(
                'iframe' => array(
                    'src'         => true,
                    'width'       => true,
                    'height'      => true,
                    'frameborder' => true,
                    'style'       => true,
                    'allowfullscreen' => true,
                    'loading'     => true,
                ),
            ) ); ?>
        </div>
        <?php endif; ?>

    </div><!-- /.container -->
</section><!-- /.contacts -->
