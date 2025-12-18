<?php
/**
 * Plugin Name: Biblioteka
 * Description: Rejestruje typ wpisu "biblioteka" z polami dodatkowymi oraz taksonomiami kategorie i przedział wiekowy wraz z gotowymi szablonami archiwum i wpisu.
 * Version: 1.0.0
 * Author: ChatGPT
 * Text Domain: biblioteka
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Biblioteka_CPT')) {
    class Biblioteka_CPT
    {
        private const POST_TYPE = 'biblioteka';
        private const TAX_CATEGORY = 'kategorie';
        private const TAX_AGE_RANGE = 'przedzial-wiekowy';

        private const META_AUTHOR = '_biblioteka_author';
        private const META_FIRST_EDITION_DATE = '_biblioteka_first_edition_date';
        private const META_CURRENT_EDITION_DATE = '_biblioteka_current_edition_date';
        private const META_GROSS_PRICE = '_biblioteka_gross_price';

        public function __construct()
        {
            add_action('init', [$this, 'register_post_type']);
            add_action('init', [$this, 'register_taxonomies']);
            add_action('init', [$this, 'register_meta_fields']);
            add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
            add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_boxes']);
            add_filter('single_template', [$this, 'filter_single_template']);
            add_filter('archive_template', [$this, 'filter_archive_template']);
        }

        public function register_post_type(): void
        {
            $labels = [
                'name' => __('Biblioteka', 'biblioteka'),
                'singular_name' => __('Pozycja biblioteczna', 'biblioteka'),
                'add_new_item' => __('Dodaj nową pozycję', 'biblioteka'),
                'edit_item' => __('Edytuj pozycję', 'biblioteka'),
                'new_item' => __('Nowa pozycja', 'biblioteka'),
                'view_item' => __('Zobacz pozycję', 'biblioteka'),
                'search_items' => __('Szukaj w bibliotece', 'biblioteka'),
                'not_found' => __('Nie znaleziono pozycji', 'biblioteka'),
                'not_found_in_trash' => __('Brak pozycji w koszu', 'biblioteka'),
                'all_items' => __('Biblioteka', 'biblioteka'),
            ];

            $args = [
                'labels' => $labels,
                'public' => true,
                'has_archive' => true,
                'rewrite' => [
                    'slug' => 'biblioteka',
                ],
                'show_in_menu' => true,
                'menu_icon' => 'dashicons-book',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
                'show_in_rest' => true,
                'hierarchical' => false,
            ];

            register_post_type(self::POST_TYPE, $args);
        }

        public function register_taxonomies(): void
        {
            register_taxonomy(
                self::TAX_CATEGORY,
                self::POST_TYPE,
                [
                    'label' => __('Kategorie', 'biblioteka'),
                    'labels' => [
                        'singular_name' => __('Kategoria', 'biblioteka'),
                        'all_items' => __('Kategorie', 'biblioteka'),
                        'edit_item' => __('Edytuj kategorię', 'biblioteka'),
                        'add_new_item' => __('Dodaj kategorię', 'biblioteka'),
                        'search_items' => __('Szukaj kategorii', 'biblioteka'),
                    ],
                    'hierarchical' => true,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'show_in_rest' => true,
                ]
            );

            register_taxonomy(
                self::TAX_AGE_RANGE,
                self::POST_TYPE,
                [
                    'label' => __('Przedział wiekowy', 'biblioteka'),
                    'labels' => [
                        'singular_name' => __('Przedział wiekowy', 'biblioteka'),
                        'all_items' => __('Przedziały wiekowe', 'biblioteka'),
                        'edit_item' => __('Edytuj przedział', 'biblioteka'),
                        'add_new_item' => __('Dodaj przedział', 'biblioteka'),
                        'search_items' => __('Szukaj przedziałów', 'biblioteka'),
                    ],
                    'hierarchical' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'show_in_rest' => true,
                ]
            );
        }

        public function register_meta_fields(): void
        {
            register_post_meta(
                self::POST_TYPE,
                self::META_AUTHOR,
                [
                    'single' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => true,
                ]
            );

            register_post_meta(
                self::POST_TYPE,
                self::META_FIRST_EDITION_DATE,
                [
                    'single' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => true,
                ]
            );

            register_post_meta(
                self::POST_TYPE,
                self::META_CURRENT_EDITION_DATE,
                [
                    'single' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => true,
                ]
            );

            register_post_meta(
                self::POST_TYPE,
                self::META_GROSS_PRICE,
                [
                    'single' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => true,
                ]
            );
        }

        public function register_meta_boxes(): void
        {
            add_meta_box(
                'biblioteka_meta_box',
                __('Informacje o publikacji', 'biblioteka'),
                [$this, 'render_meta_box'],
                self::POST_TYPE,
                'normal',
                'default'
            );
        }

        public function render_meta_box($post): void
        {
            wp_nonce_field('biblioteka_save_meta', 'biblioteka_meta_nonce');

            $author = get_post_meta($post->ID, self::META_AUTHOR, true);
            $first_edition_date = get_post_meta($post->ID, self::META_FIRST_EDITION_DATE, true);
            $current_edition_date = get_post_meta($post->ID, self::META_CURRENT_EDITION_DATE, true);
            $gross_price = get_post_meta($post->ID, self::META_GROSS_PRICE, true);
            ?>
            <p>
                <label for="biblioteka_author"><strong><?php esc_html_e('Imię i nazwisko autora', 'biblioteka'); ?></strong></label><br />
                <input type="text" name="biblioteka_author" id="biblioteka_author" class="widefat" value="<?php echo esc_attr($author); ?>" />
            </p>
            <p>
                <label for="biblioteka_first_edition"><strong><?php esc_html_e('Data wydania pierwszej edycji', 'biblioteka'); ?></strong></label><br />
                <input type="date" name="biblioteka_first_edition" id="biblioteka_first_edition" value="<?php echo esc_attr($first_edition_date); ?>" />
            </p>
            <p>
                <label for="biblioteka_current_edition"><strong><?php esc_html_e('Data wydania bieżącej edycji', 'biblioteka'); ?></strong></label><br />
                <input type="date" name="biblioteka_current_edition" id="biblioteka_current_edition" value="<?php echo esc_attr($current_edition_date); ?>" />
            </p>
            <p>
                <label for="biblioteka_gross_price"><strong><?php esc_html_e('Sugerowana cena brutto', 'biblioteka'); ?></strong></label><br />
                <input type="text" name="biblioteka_gross_price" id="biblioteka_gross_price" class="widefat" placeholder="49,99" value="<?php echo esc_attr($gross_price); ?>" />
            </p>
            <?php
        }

        public function save_meta_boxes(int $post_id): void
        {
            if (!isset($_POST['biblioteka_meta_nonce']) || !wp_verify_nonce($_POST['biblioteka_meta_nonce'], 'biblioteka_save_meta')) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $this->update_meta($post_id, self::META_AUTHOR, $_POST['biblioteka_author'] ?? '');
            $this->update_meta($post_id, self::META_FIRST_EDITION_DATE, $_POST['biblioteka_first_edition'] ?? '');
            $this->update_meta($post_id, self::META_CURRENT_EDITION_DATE, $_POST['biblioteka_current_edition'] ?? '');
            $this->update_meta($post_id, self::META_GROSS_PRICE, $_POST['biblioteka_gross_price'] ?? '');
        }

        private function update_meta(int $post_id, string $meta_key, string $value): void
        {
            $sanitized = sanitize_text_field($value);
            if ($sanitized !== '') {
                update_post_meta($post_id, $meta_key, $sanitized);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }

        public function filter_single_template(string $template): string
        {
            if (is_singular(self::POST_TYPE)) {
                $custom = plugin_dir_path(__FILE__) . 'templates/single-biblioteka.php';
                if (file_exists($custom)) {
                    return $custom;
                }
            }

            return $template;
        }

        public function filter_archive_template(string $template): string
        {
            if (is_post_type_archive(self::POST_TYPE) || is_tax([self::TAX_CATEGORY, self::TAX_AGE_RANGE])) {
                $custom = plugin_dir_path(__FILE__) . 'templates/archive-biblioteka.php';
                if (file_exists($custom)) {
                    return $custom;
                }
            }

            return $template;
        }
    }

    new Biblioteka_CPT();
}
