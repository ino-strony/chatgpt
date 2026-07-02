<?php
/**
 * Dynamic Gutenberg blocks for page sections.
 *
 * @package PikLight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function piklight_register_blocks() {
	wp_register_script(
		'piklight-blocks',
		PIKLIGHT_URI . '/assets/js/blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		PIKLIGHT_VERSION,
		true
	);

	$blocks = array(
		'piklight/hero'      => 'piklight_render_hero_block',
		'piklight/features'  => 'piklight_render_features_block',
		'piklight/offer'     => 'piklight_render_offer_block',
		'piklight/about'     => 'piklight_render_about_block',
		'piklight/products'  => 'piklight_render_products_block',
		'piklight/wholesale' => 'piklight_render_wholesale_block',
	);

	foreach ( $blocks as $name => $callback ) {
		register_block_type(
			$name,
			array(
				'editor_script'   => 'piklight-blocks',
				'render_callback' => $callback,
				'attributes'      => piklight_block_attributes(),
			)
		);
	}
}
add_action( 'init', 'piklight_register_blocks' );

function piklight_block_attributes() {
	return array(
		'title'       => array( 'type' => 'string', 'default' => '' ),
		'accent'      => array( 'type' => 'string', 'default' => '' ),
		'text'        => array( 'type' => 'string', 'default' => '' ),
		'buttonText'  => array( 'type' => 'string', 'default' => '' ),
		'buttonUrl'   => array( 'type' => 'string', 'default' => '#' ),
		'imageUrl'    => array( 'type' => 'string', 'default' => '' ),
		'items'       => array( 'type' => 'array', 'default' => array() ),
		'catalogText' => array( 'type' => 'string', 'default' => '' ),
		'catalogUrl'  => array( 'type' => 'string', 'default' => '#' ),
	);
}

function piklight_block_items( $attributes, $fallback ) {
	return empty( $attributes['items'] ) || ! is_array( $attributes['items'] ) ? $fallback : $attributes['items'];
}

function piklight_render_hero_block( $attributes ) {
	$title   = $attributes['title'] ?: 'Producent zniczy i wkładów do zniczy';
	$accent  = $attributes['accent'] ?: 'zniczy';
	$text    = $attributes['text'] ?: 'Wysoka jakość, estetyka i długi czas palenia. Polska produkcja od ponad 30 lat.';
	$image   = $attributes['imageUrl'];
	$style   = $image ? ' style="background-image:linear-gradient(90deg,#fff 0%,rgba(255,255,255,.88) 38%,rgba(255,255,255,.18) 100%),url(' . esc_url( $image ) . ')"' : '';
	$content = '<section class="piklight-hero"' . $style . '><div class="piklight-container hero-content"><h1>' . wp_kses_post( str_replace( $accent, '<span>' . esc_html( $accent ) . '</span>', esc_html( $title ) ) ) . '</h1><p>' . esc_html( $text ) . '</p><div class="hero-buttons"><a class="button button-primary" href="' . esc_url( $attributes['buttonUrl'] ?: '#oferta' ) . '">' . esc_html( $attributes['buttonText'] ?: 'Zobacz ofertę' ) . '</a><a class="button button-outline" href="' . esc_url( $attributes['catalogUrl'] ?: '#' ) . '">' . esc_html( $attributes['catalogText'] ?: 'Pobierz katalog PDF' ) . '</a></div><div class="experience-badge"><strong>30+</strong><span>lat doświadczenia</span></div></div></section>';
	return $content;
}

function piklight_render_features_block( $attributes ) {
	$items = piklight_block_items( $attributes, array( array( 'title' => 'Polska produkcja', 'text' => 'Wszystkie produkty wytwarzamy w Polsce.' ), array( 'title' => 'Wysoka jakość', 'text' => 'Sprawdzone surowce i technologie.' ), array( 'title' => 'Terminowe dostawy', 'text' => 'Szybka realizacja zamówień.' ) ) );
	$html  = '<section class="piklight-features"><div class="piklight-container feature-grid">';
	foreach ( $items as $item ) {
		$html .= '<article><span class="line-icon">◇</span><h3>' . esc_html( $item['title'] ?? '' ) . '</h3><p>' . esc_html( $item['text'] ?? '' ) . '</p></article>';
	}
	return $html . '</div></section>';
}

function piklight_render_offer_block( $attributes ) {
	$items = piklight_block_items( $attributes, array( array( 'title' => 'Znicze ozdobne' ), array( 'title' => 'Znicze zalewane' ), array( 'title' => 'Wkłady do zniczy' ), array( 'title' => 'Nowości' ), array( 'title' => 'Katalog produktów' ) ) );
	$html  = '<section id="oferta" class="piklight-section"><div class="piklight-container"><h2>Nasza <span>oferta</span></h2><div class="offer-grid">';
	foreach ( $items as $item ) {
		$image = ! empty( $item['imageUrl'] ) ? '<img src="' . esc_url( $item['imageUrl'] ) . '" alt="">' : '<div class="product-placeholder"></div>';
		$html .= '<article class="offer-card">' . $image . '<h3>' . esc_html( $item['title'] ?? '' ) . '</h3><a href="' . esc_url( $item['url'] ?? '#' ) . '" aria-label="' . esc_attr( $item['title'] ?? '' ) . '">→</a></article>';
	}
	return $html . '</div></div></section>';
}

function piklight_render_about_block( $attributes ) {
	$image = $attributes['imageUrl'] ? '<img src="' . esc_url( $attributes['imageUrl'] ) . '" alt="">' : '<div class="factory-placeholder"></div>';
	return '<section class="piklight-about"><div class="piklight-container about-grid">' . $image . '<div><h2>' . esc_html( $attributes['title'] ?: 'O firmie Pik-Light' ) . '</h2><p>' . esc_html( $attributes['text'] ?: 'Jesteśmy polskim producentem zniczy i wkładów do zniczy. Od ponad 30 lat dostarczamy produkty, które wyróżniają się estetyką, trwałością i długim czasem palenia.' ) . '</p><a class="button button-outline" href="' . esc_url( $attributes['buttonUrl'] ?: '#' ) . '">' . esc_html( $attributes['buttonText'] ?: 'Dowiedz się więcej' ) . '</a></div><aside class="stats"><strong>30+</strong><span>lat doświadczenia</span><strong>1000+</strong><span>wzorów w ofercie</span><strong>500+</strong><span>zadowolonych klientów</span></aside></div></section>';
}

function piklight_render_products_block( $attributes ) {
	$items = piklight_block_items( $attributes, array( array( 'title' => 'V1-50' ), array( 'title' => '42-O-A' ), array( 'title' => '45-O-R' ), array( 'title' => 'Anioł-A' ), array( 'title' => 'Z-401' ), array( 'title' => 'Z-115' ) ) );
	$html  = '<section class="piklight-products"><div class="piklight-container"><h2>Polecane <span>produkty</span></h2><div class="products-row">';
	foreach ( $items as $item ) {
		$image = ! empty( $item['imageUrl'] ) ? '<img src="' . esc_url( $item['imageUrl'] ) . '" alt="">' : '<div class="candle-placeholder"></div>';
		$html .= '<article class="product-card">' . $image . '<h3>' . esc_html( $item['title'] ?? '' ) . '</h3></article>';
	}
	return $html . '</div><a class="button button-outline centered" href="' . esc_url( $attributes['buttonUrl'] ?: '#' ) . '">' . esc_html( $attributes['buttonText'] ?: 'Zobacz wszystkie produkty' ) . '</a></div></section>';
}

function piklight_render_wholesale_block( $attributes ) {
	return '<section class="piklight-wholesale"><div class="piklight-container wholesale-grid"><div><h2>' . esc_html( $attributes['title'] ?: 'Współpraca hurtowa' ) . '</h2><p>' . esc_html( $attributes['text'] ?: 'Zapraszamy hurtownie i dystrybutorów do współpracy. Oferujemy szeroki asortyment, konkurencyjne ceny oraz wsparcie na każdym etapie współpracy.' ) . '</p></div><div class="wholesale-icons"><span>Indywidualne podejście</span><span>Atrakcyjne warunki</span><span>Stała dostępność produktów</span><span>Wsparcie i doradztwo</span></div><a class="button button-primary" href="' . esc_url( $attributes['buttonUrl'] ?: '#kontakt' ) . '">' . esc_html( $attributes['buttonText'] ?: 'Zapytaj o ofertę' ) . '</a></div></section>';
}
