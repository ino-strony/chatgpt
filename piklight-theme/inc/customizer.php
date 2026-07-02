<?php
/**
 * Customizer settings for editable header and footer content.
 *
 * @package PikLight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function piklight_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'piklight_header',
		array(
			'title'    => __( 'Pik-Light: Header', 'piklight' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_section(
		'piklight_footer',
		array(
			'title'    => __( 'Pik-Light: Footer', 'piklight' ),
			'priority' => 31,
		)
	);

	$settings = array(
		'logo_text'         => array( 'Pik-Light', 'text', 'piklight_header', __( 'Tekst logo', 'piklight' ) ),
		'logo_image'        => array( '', 'image', 'piklight_header', __( 'Logo graficzne', 'piklight' ) ),
		'header_phone'     => array( '+48 505-379-543', 'text', 'piklight_header', __( 'Telefon w nagłówku', 'piklight' ) ),
		'header_cta_label' => array( 'Zapytaj o ofertę', 'text', 'piklight_header', __( 'Tekst przycisku CTA', 'piklight' ) ),
		'header_cta_url'   => array( '#kontakt', 'url', 'piklight_header', __( 'Adres przycisku CTA', 'piklight' ) ),
		'footer_text'      => array( 'Producent zniczy i wkładów do zniczy. Jakość, której możesz zaufać.', 'textarea', 'piklight_footer', __( 'Opis w stopce', 'piklight' ) ),
		'footer_address'   => array( 'Sportowa 2, 88-181 Jaksice', 'text', 'piklight_footer', __( 'Adres', 'piklight' ) ),
		'footer_email'     => array( 'pik-light@wp.pl', 'email', 'piklight_footer', __( 'E-mail', 'piklight' ) ),
		'footer_phone'     => array( '+48 505-379-543', 'text', 'piklight_footer', __( 'Telefon', 'piklight' ) ),
		'footer_catalog'   => array( '#', 'url', 'piklight_footer', __( 'Link do katalogu PDF', 'piklight' ) ),
		'footer_copyright' => array( '© 2024 Pik-Light. Wszelkie prawa zastrzeżone.', 'text', 'piklight_footer', __( 'Copyright', 'piklight' ) ),
	);

	foreach ( $settings as $key => $data ) {
		list( $default, $type, $section, $label ) = $data;
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $default,
				'sanitize_callback' => piklight_customizer_sanitizer( $type ),
			)
		);

		if ( 'image' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					$key,
					array(
						'label'   => $label,
						'section' => $section,
					)
				)
			);
			continue;
		}

		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => $section,
				'type'    => $type,
			)
		);
	}
}
add_action( 'customize_register', 'piklight_customize_register' );

function piklight_customizer_sanitizer( $type ) {
	switch ( $type ) {
		case 'email':
			return 'sanitize_email';
		case 'url':
		case 'image':
			return 'esc_url_raw';
		case 'textarea':
			return 'sanitize_textarea_field';
		default:
			return 'sanitize_text_field';
	}
}
