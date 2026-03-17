<?php
/**
 * Theme header.
 *
 * @package miauto
 */

$miauto_logo       = miauto_get_option( 'miauto_logo_text' );
$miauto_slogan     = miauto_get_option( 'miauto_slogan' );
$miauto_address    = miauto_get_option( 'miauto_address' );
$miauto_phones     = miauto_get_option( 'miauto_phones' );
$miauto_stars      = (int) miauto_get_option( 'miauto_rating_stars' );
$miauto_reviews    = miauto_get_option( 'miauto_rating_reviews' );
$miauto_source     = miauto_get_option( 'miauto_rating_source' );
$miauto_online     = miauto_get_option( 'miauto_online_text' );
$miauto_callback   = miauto_get_option( 'miauto_callback_text' );
$miauto_vk         = miauto_get_option( 'miauto_vk_url' );
$miauto_tg         = miauto_get_option( 'miauto_telegram_url' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'template-parts/sections/top-bar' ); ?>

<header class="header" role="banner">
    <div class="container">

        <!-- Desktop: company-info row -->
        <div class="header__info" aria-label="Информация о компании">

            <!-- Brand + slogan -->
            <div class="header__brand">
                <a class="header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $miauto_logo ); ?></a>
                <span class="header__slogan"><?php echo esc_html( $miauto_slogan ); ?></span>
            </div>

            <!-- Address -->
            <address class="header__address" aria-label="Адрес">
                <svg class="header__address-icon" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 0C3.58 0 0 3.58 0 8C0 13.25 8 20 8 20C8 20 16 13.25 16 8C16 3.58 12.42 0 8 0ZM8 10.5C6.62 10.5 5.5 9.38 5.5 8C5.5 6.62 6.62 5.5 8 5.5C9.38 5.5 10.5 6.62 10.5 8C10.5 9.38 9.38 10.5 8 10.5Z" fill="currentColor"/>
                </svg>
                <span class="header__address-text"><?php echo esc_html( $miauto_address ); ?></span>
            </address>

            <!-- Rating -->
            <div class="header__rating" aria-label="Рейтинг">
                <div class="header__rating-row">
                    <div class="header__stars" aria-label="<?php echo esc_attr( $miauto_stars ); ?> звёзд">
                        <?php for ( $i = 0; $i < $miauto_stars; $i++ ) : ?>
                        <svg class="header__star" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8.5 1L10.7 6.3L16.5 6.9L12.3 10.6L13.6 16.3L8.5 13.3L3.4 16.3L4.7 10.6L0.5 6.9L6.3 6.3L8.5 1Z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="header__reviews"><?php echo esc_html( $miauto_reviews ); ?></span>
                </div>
                <span class="header__rating-source"><?php echo esc_html( $miauto_source ); ?></span>
            </div>

            <!-- Phones -->
            <?php if ( ! empty( $miauto_phones ) ) : ?>
            <div class="header__phones">
                <?php foreach ( $miauto_phones as $phone ) : ?>
                <a class="header__phone" href="tel:<?php echo esc_attr( $phone['raw'] ); ?>" aria-label="Позвонить <?php echo esc_attr( $phone['number'] ); ?>">
                    <svg class="header__phone-icon" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3.6 1H6.8L8.4 5.2L6.4 6.4C7.3 8.2 9.8 10.7 11.6 11.6L12.8 9.6L17 11.2V14.4C17 15.8 15.8 17 14.4 17C6.6 17 1 11.4 1 3.6C1 2.2 2.2 1 3.6 1Z"/>
                    </svg>
                    <span><?php echo esc_html( $phone['number'] ); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /.header__info -->

        <!-- Desktop: nav bar -->
        <div class="header__nav-bar">

            <nav class="header__nav" aria-label="Основная навигация">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'header__nav-list',
                    'walker'         => new Miauto_Nav_Walker(),
                    'fallback_cb'    => false,
                ) );
                ?>
            </nav>

            <!-- Online status -->
            <div class="header__online" aria-label="Статус онлайн">
                <span class="header__online-dot" aria-hidden="true"></span>
                <span class="header__online-text"><?php echo esc_html( $miauto_online ); ?></span>
            </div>

            <!-- Social icons -->
            <div class="header__social" aria-label="Социальные сети">
                <?php if ( ! empty( $miauto_vk ) ) : ?>
                <a class="header__social-link" href="<?php echo esc_url( $miauto_vk ); ?>" aria-label="ВКонтакте" rel="noopener">
                    <svg class="header__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10.3 14.7H11.7C11.7 14.7 12.1 14.65 12.3 14.43C12.5 14.23 12.49 13.85 12.49 13.85C12.49 13.85 12.46 12.33 13.18 12.1C13.89 11.88 14.81 13.58 15.79 14.22C16.53 14.7 17.1 14.6 17.1 14.6L19.7 14.57C19.7 14.57 21.07 14.49 20.41 13.43C20.36 13.35 20.02 12.62 18.35 11.08C16.6 9.47 16.84 9.73 18.93 6.93C20.2 5.22 20.71 4.17 20.55 3.7C20.4 3.25 19.46 3.37 19.46 3.37L16.53 3.39C16.53 3.39 16.31 3.36 16.14 3.46C15.98 3.55 15.87 3.76 15.87 3.76C15.87 3.76 15.37 5.09 14.71 6.22C13.32 8.62 12.77 8.74 12.54 8.59C12.01 8.24 12.14 7.2 12.14 6.45C12.14 4.07 12.5 3.07 11.41 2.81C11.05 2.72 10.78 2.66 9.89 2.65C8.74 2.64 7.77 2.66 7.22 2.93C6.85 3.11 6.57 3.51 6.73 3.53C6.93 3.56 7.38 3.66 7.62 3.98C7.93 4.4 7.92 5.35 7.92 5.35C7.92 5.35 8.1 8.27 7.47 8.62C7.04 8.86 6.44 8.36 5.21 6.19C4.57 5.08 4.08 3.85 4.08 3.85C4.08 3.85 3.98 3.64 3.82 3.53C3.63 3.39 3.36 3.34 3.36 3.34L0.57 3.36C0.57 3.36 0.15 3.37 0 3.55C-0.13 3.71 0 4.05 0 4.05C0 4.05 2.25 9.34 4.79 12C7.11 14.44 9.74 14.27 9.74 14.27L10.3 14.27V14.7Z" transform="translate(0 2.5)"/>
                    </svg>
                </a>
                <?php endif; ?>
                <?php if ( ! empty( $miauto_tg ) ) : ?>
                <a class="header__social-link" href="<?php echo esc_url( $miauto_tg ); ?>" aria-label="Telegram" rel="noopener">
                    <svg class="header__social-icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM14.64 6.8L12.97 14.56C12.85 15.1 12.52 15.23 12.06 14.97L9.56 13.13L8.35 14.3C8.22 14.43 8.1 14.54 7.84 14.54L8.02 11.99L12.66 7.79C12.87 7.6 12.61 7.5 12.33 7.69L6.57 11.32L4.1 10.56C3.56 10.39 3.55 10.01 4.22 9.75L13.95 6.04C14.4 5.87 14.8 6.14 14.64 6.8Z"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>

            <!-- Callback button -->
            <button class="header__callback" type="button"><?php echo esc_html( $miauto_callback ); ?></button>

        </div><!-- /.header__nav-bar -->

    </div><!-- /.container -->

    <!-- Mobile header bar -->
    <div class="header__mobile">
        <div class="header__mobile-left">
            <button class="header__burger" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobile-drawer">
                <span class="header__burger-icon" aria-hidden="true">
                    <span class="header__burger-line"></span>
                    <span class="header__burger-line"></span>
                    <span class="header__burger-line"></span>
                </span>
            </button>
            <a class="header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $miauto_logo ); ?></a>
        </div>

        <?php if ( ! empty( $miauto_phones ) ) : ?>
        <div class="header__mobile-phones">
            <?php foreach ( $miauto_phones as $phone ) : ?>
            <a class="header__mobile-phone" href="tel:<?php echo esc_attr( $phone['raw'] ); ?>" aria-label="<?php echo esc_attr( $phone['number'] ); ?>">
                <svg class="header__mobile-phone-icon" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.6 1H6.8L8.4 5.2L6.4 6.4C7.3 8.2 9.8 10.7 11.6 11.6L12.8 9.6L17 11.2V14.4C17 15.8 15.8 17 14.4 17C6.6 17 1 11.4 1 3.6C1 2.2 2.2 1 3.6 1Z"/>
                </svg>
                <span><?php echo esc_html( $phone['number'] ); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div><!-- /.header__mobile -->

    <!-- Mobile drawer -->
    <div class="header__drawer" id="mobile-drawer" role="dialog" aria-modal="true" aria-label="Мобильное меню">
        <div class="header__drawer-overlay" tabindex="-1"></div>
        <div class="header__drawer-panel">
            <button class="header__drawer-close" type="button" aria-label="Закрыть меню">
                <svg viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M1 1L13 13M13 1L1 13" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <span class="header__drawer-logo"><?php echo esc_html( $miauto_logo ); ?></span>
            <nav class="header__drawer-nav" aria-label="Мобильная навигация">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'mobile',
                    'container'      => false,
                    'menu_class'     => 'header__drawer-nav-list',
                    'walker'         => new Miauto_Nav_Walker(),
                    'fallback_cb'    => false,
                ) );
                ?>
            </nav>
            <?php if ( ! empty( $miauto_phones ) ) : ?>
            <div class="header__drawer-phones">
                <?php foreach ( $miauto_phones as $phone ) : ?>
                <a class="header__drawer-phone" href="tel:<?php echo esc_attr( $phone['raw'] ); ?>">
                    <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3.6 1H6.8L8.4 5.2L6.4 6.4C7.3 8.2 9.8 10.7 11.6 11.6L12.8 9.6L17 11.2V14.4C17 15.8 15.8 17 14.4 17C6.6 17 1 11.4 1 3.6C1 2.2 2.2 1 3.6 1Z"/>
                    </svg>
                    <?php echo esc_html( $phone['number'] ); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /.header__drawer -->

</header><!-- /.header -->

<main>
