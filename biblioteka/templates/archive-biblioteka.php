<?php
/**
 * Szablon archiwum typu wpisu "biblioteka".
 *
 * @package Biblioteka_CPT
 */

get_header();

$archive_title = post_type_archive_title('', false);
$archive_description = get_the_archive_description();
?>

<main class="biblioteka-archive" aria-label="<?php esc_attr_e('Lista pozycji bibliotecznych', 'biblioteka'); ?>">
    <header class="biblioteka-archive__header">
        <h1 class="biblioteka-archive__title"><?php echo esc_html($archive_title); ?></h1>
        <?php if ($archive_description) : ?>
            <div class="biblioteka-archive__description"><?php echo wp_kses_post($archive_description); ?></div>
        <?php endif; ?>
        <?php if (is_tax()) : ?>
            <p class="biblioteka-archive__taxonomy-label">
                <?php esc_html_e('Filtrowanie według:', 'biblioteka'); ?>
                <strong><?php single_term_title(); ?></strong>
            </p>
        <?php endif; ?>
    </header>

    <?php if (have_posts()) : ?>
        <section class="biblioteka-archive__grid">
            <?php
            while (have_posts()) :
                the_post();
                $author = get_post_meta(get_the_ID(), '_biblioteka_author', true);
                $first_edition = get_post_meta(get_the_ID(), '_biblioteka_first_edition_date', true);
                $current_edition = get_post_meta(get_the_ID(), '_biblioteka_current_edition_date', true);
                $gross_price = get_post_meta(get_the_ID(), '_biblioteka_gross_price', true);
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('biblioteka-card'); ?>>
                    <a class="biblioteka-card__link" href="<?php the_permalink(); ?>">
                        <div class="biblioteka-card__content">
                            <h2 class="biblioteka-card__title"><?php the_title(); ?></h2>
                            <?php if ($author) : ?>
                                <p class="biblioteka-card__meta">
                                    <strong><?php esc_html_e('Autor:', 'biblioteka'); ?></strong>
                                    <span><?php echo esc_html($author); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($first_edition || $current_edition) : ?>
                                <p class="biblioteka-card__meta">
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
                                <p class="biblioteka-card__price">
                                    <strong><?php esc_html_e('Cena brutto:', 'biblioteka'); ?></strong>
                                    <span><?php echo esc_html($gross_price); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="biblioteka-card__footer">
                            <?php
                            $categories = get_the_terms(get_the_ID(), 'kategorie');
                            $age_ranges = get_the_terms(get_the_ID(), 'przedzial-wiekowy');
                            if ($categories || $age_ranges) :
                                ?>
                                <ul class="biblioteka-card__taxonomies">
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
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>
        </section>

        <nav class="biblioteka-pagination" aria-label="<?php esc_attr_e('Stronicowanie', 'biblioteka'); ?>">
            <?php the_posts_pagination(); ?>
        </nav>
    <?php else : ?>
        <p class="biblioteka-archive__empty"><?php esc_html_e('Brak pozycji do wyświetlenia.', 'biblioteka'); ?></p>
    <?php endif; ?>
</main>

<?php get_footer();
