<?php
/**
 * Szablon pojedynczego wpisu typu "biblioteka".
 *
 * @package Biblioteka_CPT
 */

get_header();

$post_id = get_the_ID();
$author = get_post_meta($post_id, '_biblioteka_author', true);
$first_edition = get_post_meta($post_id, '_biblioteka_first_edition_date', true);
$current_edition = get_post_meta($post_id, '_biblioteka_current_edition_date', true);
$gross_price = get_post_meta($post_id, '_biblioteka_gross_price', true);
$categories = get_the_terms($post_id, 'kategorie');
$age_ranges = get_the_terms($post_id, 'przedzial-wiekowy');
?>

<main class="biblioteka-single" aria-label="<?php esc_attr_e('Pozycja biblioteczna', 'biblioteka'); ?>">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('biblioteka-single__article'); ?>>
            <header class="biblioteka-single__header">
                <h1 class="biblioteka-single__title"><?php the_title(); ?></h1>
                <?php if ($author) : ?>
                    <p class="biblioteka-single__meta">
                        <strong><?php esc_html_e('Autor:', 'biblioteka'); ?></strong>
                        <span><?php echo esc_html($author); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($first_edition || $current_edition) : ?>
                    <p class="biblioteka-single__meta">
                        <strong><?php esc_html_e('Edycje:', 'biblioteka'); ?></strong>
                        <span>
                            <?php if ($first_edition) : ?>
                                <?php esc_html_e('Pierwsza:', 'biblioteka'); ?> <?php echo esc_html($first_edition); ?>
                            <?php endif; ?>
                            <?php if ($first_edition && $current_edition) : ?> | <?php endif; ?>
                            <?php if ($current_edition) : ?>
                                <?php esc_html_e('Bieżąca:', 'biblioteka'); ?> <?php echo esc_html($current_edition); ?>
                            <?php endif; ?>
                        </span>
                    </p>
                <?php endif; ?>
                <?php if ($gross_price) : ?>
                    <p class="biblioteka-single__price">
                        <strong><?php esc_html_e('Sugerowana cena brutto:', 'biblioteka'); ?></strong>
                        <span><?php echo esc_html($gross_price); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($categories || $age_ranges) : ?>
                    <ul class="biblioteka-single__taxonomies">
                        <?php if ($categories) : ?>
                            <li>
                                <strong><?php esc_html_e('Kategorie', 'biblioteka'); ?>:</strong>
                                <?php echo esc_html(join(', ', wp_list_pluck($categories, 'name'))); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($age_ranges) : ?>
                            <li>
                                <strong><?php esc_html_e('Przedział wiekowy', 'biblioteka'); ?>:</strong>
                                <?php echo esc_html(join(', ', wp_list_pluck($age_ranges, 'name'))); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </header>

            <div class="biblioteka-single__content">
                <?php the_content(); ?>
            </div>
        </article>

        <nav class="biblioteka-single__navigation" aria-label="<?php esc_attr_e('Nawigacja wpisów', 'biblioteka'); ?>">
            <?php the_post_navigation(); ?>
        </nav>
    <?php endwhile; endif; ?>
</main>

<?php get_footer();
