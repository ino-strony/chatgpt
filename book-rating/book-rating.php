<?php
/**
 * Plugin Name: Book Category Star Rating
 * Description: Dodaje formularz ocen gwiazdkowych (0–5) dla wpisów z kategorii „książki/ksiazki”. Każdy zalogowany użytkownik może dodać lub zaktualizować swoją ocenę, a formularz wyświetla się pod tytułem wpisu.
 * Version: 1.0.0
 * Author: ChatGPT
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BSR_PLUGIN_VERSION = '1.0.0';
const BSR_META_KEY       = '_bsr_ratings';

/**
 * Sprawdza, czy bieżący wpis należy do kategorii „książki/ksiazki”.
 *
 * @param WP_Post $post Post object.
 *
 * @return bool
 */
function bsr_is_books_post( $post ) {
	return $post instanceof WP_Post && has_category( array( 'książki', 'ksiazki' ), $post );
}

/**
 * Rejestruje zasoby front-endowe.
 */
function bsr_register_assets() {
	wp_register_style(
		'bsr-styles',
		plugins_url( 'assets/book-rating.css', __FILE__ ),
		array(),
		BSR_PLUGIN_VERSION
	);

	wp_register_script(
		'bsr-scripts',
		plugins_url( 'assets/book-rating.js', __FILE__ ),
		array( 'jquery' ),
		BSR_PLUGIN_VERSION,
		true
	);
}
add_action( 'init', 'bsr_register_assets' );

/**
 * Dodaje formularz oceny na początku treści wpisu.
 *
 * @param string $content Treść wpisu.
 *
 * @return string
 */
function bsr_inject_rating_form( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();

	if ( ! bsr_is_books_post( $post ) ) {
		return $content;
	}

	$block = bsr_render_rating_block( $post );

	return $block . $content;
}
add_filter( 'the_content', 'bsr_inject_rating_form', 9 );

/**
 * Renderuje blok oceny.
 *
 * @param WP_Post $post Post object.
 *
 * @return string
 */
function bsr_render_rating_block( WP_Post $post ) {
	bsr_enqueue_assets( $post->ID );

	$is_logged_in = is_user_logged_in();
	$user_id      = get_current_user_id();
	$user_rating  = $is_logged_in ? bsr_get_user_rating( $post->ID, $user_id ) : null;
	$stats        = bsr_get_rating_stats( $post->ID );
	$nonce        = wp_create_nonce( 'bsr_rate_post_' . $post->ID );

	ob_start();
	?>
	<section class="bsr-rating" aria-label="<?php esc_attr_e( 'Ocena wpisu', 'book-category-star-rating' ); ?>">
		<header class="bsr-rating__header">
			<strong><?php esc_html_e( 'Oceń ten wpis (0–5 gwiazdek)', 'book-category-star-rating' ); ?></strong>
		</header>
		<?php if ( $is_logged_in ) : ?>
			<form class="bsr-rating-form" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<div class="bsr-rating__stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Twoja ocena', 'book-category-star-rating' ); ?>">
					<?php for ( $i = 5; $i >= 0; $i-- ) : ?>
						<?php
						$input_id = 'bsr-rating-' . $post->ID . '-' . $i;
						?>
						<input type="radio" id="<?php echo esc_attr( $input_id ); ?>" name="bsr_rating" value="<?php echo esc_attr( $i ); ?>" <?php checked( $user_rating, $i ); ?> />
						<label for="<?php echo esc_attr( $input_id ); ?>" aria-label="<?php echo esc_attr( sprintf( '%d z 5', $i ) ); ?>" title="<?php echo esc_attr( sprintf( '%d z 5', $i ) ); ?>">★</label>
					<?php endfor; ?>
				</div>
				<div class="bsr-rating__actions">
					<button type="submit" class="button"><?php esc_html_e( 'Zapisz ocenę', 'book-category-star-rating' ); ?></button>
					<span class="bsr-rating__user-current">
						<?php
						echo esc_html(
							$user_rating !== null
								? sprintf(
									/* translators: %s - user rating */
									__( 'Twoja ocena: %s/5', 'book-category-star-rating' ),
									$user_rating
								)
								: __( 'Nie dodałeś jeszcze oceny', 'book-category-star-rating' )
						);
						?>
					</span>
				</div>
				<p class="bsr-rating__message" role="status" aria-live="polite"></p>
			</form>
		<?php else : ?>
			<p class="bsr-rating__notice">
				<?php esc_html_e( 'Zaloguj się, aby dodać ocenę w tym wpisie.', 'book-category-star-rating' ); ?>
			</p>
		<?php endif; ?>
		<p class="bsr-rating__average">
			<?php
			echo esc_html(
				$stats['count']
					? sprintf(
						/* translators: 1: average rating, 2: votes count */
						__( 'Średnia ocena: %1$s/5 (głosów: %2$d)', 'book-category-star-rating' ),
						number_format_i18n( $stats['average'], 1 ),
						$stats['count']
					)
					: __( 'Brak ocen. Bądź pierwszy!', 'book-category-star-rating' )
			);
			?>
		</p>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * Enqueue front-end assets when potrzebne.
 *
 * @param int $post_id Post ID.
 */
function bsr_enqueue_assets( $post_id ) {
	wp_enqueue_style( 'bsr-styles' );
	wp_enqueue_script( 'bsr-scripts' );

	wp_localize_script(
		'bsr-scripts',
		'bsrRating',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'strings' => array(
				'saving'  => __( 'Trwa zapisywanie…', 'book-category-star-rating' ),
				'success' => __( 'Twoja ocena została zapisana.', 'book-category-star-rating' ),
				'error'   => __( 'Wystąpił błąd podczas zapisywania oceny.', 'book-category-star-rating' ),
			),
		)
	);
}

/**
 * Pobiera wszystkie oceny powiązane z wpisem.
 *
 * @param int $post_id Post ID.
 *
 * @return array
 */
function bsr_get_all_ratings( $post_id ) {
	$ratings = get_post_meta( $post_id, BSR_META_KEY, true );

	if ( ! is_array( $ratings ) ) {
		$ratings = array();
	}

	return $ratings;
}

/**
 * Pobiera ocenę użytkownika.
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 *
 * @return int|null
 */
function bsr_get_user_rating( $post_id, $user_id ) {
	$ratings = bsr_get_all_ratings( $post_id );

	if ( isset( $ratings[ $user_id ] ) ) {
		return (int) $ratings[ $user_id ];
	}

	return null;
}

/**
 * Zapisuje ocenę użytkownika.
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 * @param int $rating  Rating (0-5).
 *
 * @return array Aktualne statystyki.
 */
function bsr_store_user_rating( $post_id, $user_id, $rating ) {
	$ratings             = bsr_get_all_ratings( $post_id );
	$ratings[ $user_id ] = $rating;

	update_post_meta( $post_id, BSR_META_KEY, $ratings );

	return bsr_get_rating_stats( $post_id );
}

/**
 * Zwraca statystyki ocen wpisu.
 *
 * @param int $post_id Post ID.
 *
 * @return array
 */
function bsr_get_rating_stats( $post_id ) {
	$ratings = bsr_get_all_ratings( $post_id );
	$count   = count( $ratings );

	if ( 0 === $count ) {
		return array(
			'count'   => 0,
			'average' => 0,
		);
	}

	$sum = array_sum( array_map( 'intval', $ratings ) );

	return array(
		'count'   => $count,
		'average' => $sum / $count,
	);
}

/**
 * AJAX: zapisuje ocenę użytkownika.
 */
function bsr_save_rating() {
	$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
	$rating  = isset( $_POST['rating'] ) ? (int) $_POST['rating'] : -1;

	if ( ! $post_id || ! check_ajax_referer( 'bsr_rate_post_' . $post_id, 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Nieprawidłowe żądanie.', 'book-category-star-rating' ) ), 400 );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Musisz być zalogowany.', 'book-category-star-rating' ) ), 403 );
	}

	$post = get_post( $post_id );

	if ( ! $post || ! bsr_is_books_post( $post ) ) {
		wp_send_json_error( array( 'message' => __( 'Ten wpis nie podlega ocenie.', 'book-category-star-rating' ) ), 400 );
	}

	$rating = max( 0, min( 5, $rating ) );
	$user   = get_current_user_id();

	$stats = bsr_store_user_rating( $post_id, $user, $rating );

	$average_text = $stats['count']
		? sprintf(
			/* translators: 1: average rating, 2: votes count */
			__( 'Średnia ocena: %1$s/5 (głosów: %2$d)', 'book-category-star-rating' ),
			number_format_i18n( $stats['average'], 1 ),
			$stats['count']
		)
		: __( 'Brak ocen. Bądź pierwszy!', 'book-category-star-rating' );

	wp_send_json_success(
		array(
			'userRating'  => $rating,
			'average'     => $stats['average'],
			'count'       => $stats['count'],
			'averageText' => $average_text,
			'userText'    => sprintf(
				/* translators: %s - user rating */
				__( 'Twoja ocena: %s/5', 'book-category-star-rating' ),
				$rating
			),
		)
	);
}
add_action( 'wp_ajax_bsr_save_rating', 'bsr_save_rating' );
