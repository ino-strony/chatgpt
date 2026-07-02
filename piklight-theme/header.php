<?php
/**
 * Header template.
 *
 * @package PikLight
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<div class="piklight-container header-inner">
		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php $logo_image = piklight_get_theme_option( 'logo_image' ); ?>
			<?php if ( $logo_image ) : ?>
				<img src="<?php echo esc_url( $logo_image ); ?>" alt="<?php echo esc_attr( piklight_get_theme_option( 'logo_text', 'Pik-Light' ) ); ?>">
			<?php else : ?>
				<span><?php echo esc_html( piklight_get_theme_option( 'logo_text', 'Pik-Light' ) ); ?></span>
			<?php endif; ?>
		</a>
		<nav class="primary-navigation" aria-label="<?php esc_attr_e( 'Menu główne', 'piklight' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'piklight_fallback_menu',
				)
			);
			?>
		</nav>
		<div class="header-actions">
			<a class="header-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', piklight_get_theme_option( 'header_phone', '+48 505-379-543' ) ) ); ?>"><?php echo esc_html( piklight_get_theme_option( 'header_phone', '+48 505-379-543' ) ); ?></a>
			<a class="button button-primary" href="<?php echo esc_url( piklight_get_theme_option( 'header_cta_url', '#kontakt' ) ); ?>"><?php echo esc_html( piklight_get_theme_option( 'header_cta_label', 'Zapytaj o ofertę' ) ); ?></a>
		</div>
	</div>
</header>
<?php
if ( ! function_exists( 'piklight_fallback_menu' ) ) {
	function piklight_fallback_menu() {
		echo '<ul class="menu"><li><a href="#">' . esc_html__( 'Strona główna', 'piklight' ) . '</a></li><li><a href="#oferta">' . esc_html__( 'Oferta', 'piklight' ) . '</a></li><li><a href="#kontakt">' . esc_html__( 'Kontakt', 'piklight' ) . '</a></li></ul>';
	}
}
