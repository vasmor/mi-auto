<?php
/**
 * Theme footer.
 *
 * @package miauto
 */

$miauto_logo            = miauto_get_option( 'miauto_logo_text' );
$miauto_slogan          = miauto_get_option( 'miauto_slogan' );
$miauto_address         = miauto_get_option( 'miauto_address' );
$miauto_hours_short     = miauto_get_option( 'miauto_hours_short' );
$miauto_email           = miauto_get_option( 'miauto_email' );
$miauto_phones          = miauto_get_option( 'miauto_phones' );
$miauto_vk              = miauto_get_option( 'miauto_vk_url' );
$miauto_tg              = miauto_get_option( 'miauto_telegram_url' );
$miauto_footer_partners = miauto_get_option( 'miauto_footer_partners' );
$miauto_footer_adv      = miauto_get_option( 'miauto_footer_advantages' );
$miauto_privacy_text    = miauto_get_option( 'miauto_footer_privacy_text' );
$miauto_privacy_url     = miauto_get_option( 'miauto_footer_privacy_url' );
$miauto_developer       = miauto_get_option( 'miauto_footer_developer_text' );
?>

</main>

<footer class="footer">
    <div class="footer__container">

        <!-- Brand column -->
        <div class="footer__brand">
            <div class="footer__brand-group">
                <a class="footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $miauto_logo ); ?></a>
                <p class="footer__slogan"><?php echo esc_html( $miauto_slogan ); ?></p>
            </div>
            <?php if ( ! empty( $miauto_footer_partners ) ) : ?>
            <div class="footer__partners">
                <span class="footer__column-title">Наши партнеры</span>
                <div class="footer__column-list">
                    <?php foreach ( $miauto_footer_partners as $partner ) : ?>
                    <a class="footer__partner-link" href="<?php echo esc_url( $partner['url'] ); ?>"><?php echo esc_html( $partner['title'] ); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Menu column -->
        <div class="footer__column" data-accordion>
            <span class="footer__column-title">
                Меню
                <svg class="footer__toggle" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 4L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="footer__column-list">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer',
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'fallback_cb'    => false,
                ) );
                ?>
            </div>
        </div>
        <hr class="footer__separator">

        <!-- Services column -->
        <div class="footer__column" data-accordion>
            <span class="footer__column-title">
                Услуги
                <svg class="footer__toggle" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 4L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="footer__column-list">
                <?php
                $miauto_footer_services = new WP_Query( array(
                    'post_type'      => 'miauto_service',
                    'posts_per_page' => -1,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                ) );
                if ( $miauto_footer_services->have_posts() ) :
                    while ( $miauto_footer_services->have_posts() ) :
                        $miauto_footer_services->the_post();
                        ?>
                        <a class="footer__column-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        <hr class="footer__separator">

        <!-- Advantages column -->
        <?php if ( ! empty( $miauto_footer_adv ) ) : ?>
        <div class="footer__column" data-accordion>
            <span class="footer__column-title">
                Преимущества
                <svg class="footer__toggle" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 4L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="footer__column-list">
                <?php foreach ( $miauto_footer_adv as $adv ) : ?>
                <a class="footer__column-link" href="<?php echo esc_url( $adv['url'] ); ?>"><?php echo esc_html( $adv['title'] ); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <hr class="footer__separator">
        <?php endif; ?>

        <!-- Contacts column -->
        <div class="footer__column" data-accordion>
            <span class="footer__column-title">
                Контакты
                <svg class="footer__toggle" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 4L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="footer__column-list footer__contacts-list">
                <!-- Address -->
                <div class="footer__contact-item">
                    <svg class="footer__contact-icon" viewBox="0 0 16 19" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.343 2.305C3.844.829 5.878 0 8 0c2.122 0 4.157.829 5.657 2.305C15.157 3.781 16 5.783 16 7.87c0 2.087-.843 4.089-2.343 5.565L8 19l-5.657-5.565A7.89 7.89 0 010 7.87C0 5.783.843 3.781 2.343 2.305zM8 10.119a2.286 2.286 0 001.616-.659A2.249 2.249 0 0010.286 7.87 2.249 2.249 0 009.616 6.28 2.286 2.286 0 008 5.622c-.606 0-1.188.237-1.616.659A2.249 2.249 0 005.714 7.87c0 .596.241 1.168.67 1.59A2.286 2.286 0 008 10.119z" fill="#EA3323"/>
                    </svg>
                    <span class="footer__contact-text"><?php echo esc_html( $miauto_address ); ?></span>
                </div>
                <!-- Hours -->
                <div class="footer__contact-item">
                    <svg class="footer__contact-icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 0C3.582 0 0 3.582 0 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm3.692 9.231H8a.615.615 0 01-.615-.616V3.077a.615.615 0 111.23 0V8h3.077a.615.615 0 110 1.231z" fill="#EA3323"/>
                    </svg>
                    <span class="footer__contact-text"><?php echo esc_html( $miauto_hours_short ); ?></span>
                </div>
                <!-- Phones -->
                <?php if ( ! empty( $miauto_phones ) ) : ?>
                <div class="footer__phones">
                    <?php foreach ( $miauto_phones as $phone ) : ?>
                    <div class="footer__phone-row">
                        <svg class="footer__contact-icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.885.51A1.8 1.8 0 013.227.003c.494.031.966.271 1.268.67l1.795 2.306a1.544 1.544 0 01.315 1.494l-.547 2.19a.534.534 0 00.178.643l2.457 2.457a.534.534 0 00.644.178l2.189-.547a1.544 1.544 0 011.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702A16.1 16.1 0 014.568 11.43 16.1 16.1 0 01.148 4.421c-.362-1.03-.036-2.137.704-2.877L1.885.51z" fill="#EA3323"/>
                        </svg>
                        <a class="footer__phone-text" href="tel:<?php echo esc_attr( $phone['raw'] ); ?>"><?php echo esc_html( $phone['number'] ); ?></a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <!-- Email -->
                <div class="footer__contact-item">
                    <svg class="footer__contact-icon" viewBox="0 0 16 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14 0H2C.895.001.001.95 0 2.227v9.546C.001 13.05.895 14 2 14h12c1.105-.001 1.999-.95 2-2.227V2.227C15.999.95 15.105.001 14 0zm-.506 3.684L8.35 8.139a.493.493 0 01-.7 0L2.506 3.684a.55.55 0 01.702-.005L8 6.83l4.792-4.151a.55.55 0 01.702.005z" fill="#EA3323"/>
                    </svg>
                    <a class="footer__contact-text" href="mailto:<?php echo esc_attr( $miauto_email ); ?>"><?php echo esc_html( $miauto_email ); ?></a>
                </div>
                <!-- Socials -->
                <div class="footer__socials">
                    <?php if ( ! empty( $miauto_tg ) ) : ?>
                    <a class="footer__social" href="<?php echo esc_url( $miauto_tg ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
                        <svg class="footer__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.24-1.59.15-.15 2.71-2.48 2.76-2.69a.21.21 0 00-.05-.19c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ( ! empty( $miauto_vk ) ) : ?>
                    <a class="footer__social" href="<?php echo esc_url( $miauto_vk ); ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте">
                        <svg class="footer__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.215 19.944c-1.962 0-2.875-.288-4.46-1.44C4.752 19.8 1.576 20.812 1.438 19.08c0-1.3-.287-2.398-.612-3.597C.44 14.006 0 12.36 0 9.977 0 4.283 4.652 0 10.163 0c5.516 0 9.838 4.494 9.838 10.03.018 5.449-4.36 9.885-9.786 9.914zM10.296 4.92c-2.684-.139-4.776 1.727-5.239 4.653-.382 2.422.296 5.372.874 5.526.277.067.974-.499 1.409-.936a4.51 4.51 0 002.426.868c2.78.135 5.157-1.991 5.344-4.782.108-2.796-2.033-5.164-4.814-5.324v-.005z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr class="footer__separator">

        <!-- Partners column (mobile accordion only) -->
        <?php if ( ! empty( $miauto_footer_partners ) ) : ?>
        <div class="footer__column -mobile-only" data-accordion>
            <span class="footer__column-title">
                Наши партнеры
                <svg class="footer__toggle" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 4L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="footer__column-list">
                <?php foreach ( $miauto_footer_partners as $partner ) : ?>
                <a class="footer__partner-link" href="<?php echo esc_url( $partner['url'] ); ?>"><?php echo esc_html( $partner['title'] ); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <hr class="footer__separator">
        <?php endif; ?>

        <!-- Bottom bar -->
        <div class="footer__bottom">
            <span class="footer__bottom-text">
                <?php if ( ! empty( $miauto_privacy_url ) ) : ?>
                <a href="<?php echo esc_url( $miauto_privacy_url ); ?>"><?php echo esc_html( $miauto_privacy_text ); ?></a>
                <?php else : ?>
                <?php echo esc_html( $miauto_privacy_text ); ?>
                <?php endif; ?>
            </span>
            <span class="footer__bottom-text"><?php echo esc_html( $miauto_developer ); ?></span>
        </div>

    </div><!-- /.footer__container -->
</footer><!-- /.footer -->

<!-- Scroll-to-top -->
<button class="scroll-top" type="button" aria-label="Вернуться наверх">
    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M10 16V4M10 4L4 10M10 4L16 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>

<?php wp_footer(); ?>
</body>
</html>
