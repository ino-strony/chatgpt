<?php
/**
 * Footer template.
 *
 * @package PikLight
 */
?>
<footer id="kontakt" class="site-footer">
	<div class="piklight-container footer-grid">
		<div>
			<a class="site-logo footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( piklight_get_theme_option( 'logo_text', 'Pik-Light' ) ); ?></a>
			<p><?php echo esc_html( piklight_get_theme_option( 'footer_text' ) ); ?></p>
		</div>
		<div>
			<h2><?php esc_html_e( 'Nawigacja', 'piklight' ); ?></h2>
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'fallback_cb' => false ) ); ?>
		</div>
		<div>
			<h2><?php esc_html_e( 'Kontakt', 'piklight' ); ?></h2>
			<ul class="footer-contact">
				<li><?php echo esc_html( piklight_get_theme_option( 'footer_address' ) ); ?></li>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', piklight_get_theme_option( 'footer_phone' ) ) ); ?>"><?php echo esc_html( piklight_get_theme_option( 'footer_phone' ) ); ?></a></li>
				<li><a href="mailto:<?php echo esc_attr( piklight_get_theme_option( 'footer_email' ) ); ?>"><?php echo esc_html( piklight_get_theme_option( 'footer_email' ) ); ?></a></li>
			</ul>
		</div>
		<div>
			<h2><?php esc_html_e( 'Katalog', 'piklight' ); ?></h2>
			<p><?php esc_html_e( 'Pobierz aktualny katalog produktów (PDF)', 'piklight' ); ?></p>
			<a class="button button-outline" href="<?php echo esc_url( piklight_get_theme_option( 'footer_catalog', '#' ) ); ?>"><?php esc_html_e( 'Pobierz PDF', 'piklight' ); ?></a>
		</div>
	</div>
	<div class="piklight-container footer-bottom">
		<span><?php echo esc_html( piklight_get_theme_option( 'footer_copyright' ) ); ?></span>
		<a href="#"><?php esc_html_e( 'Polityka prywatności', 'piklight' ); ?></a>
		<a href="#"><?php esc_html_e( 'Polityka cookies', 'piklight' ); ?></a>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
