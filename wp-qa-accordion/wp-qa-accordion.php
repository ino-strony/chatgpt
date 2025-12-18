<?php
/**
 * Plugin Name: Simple Q&A Accordion
 * Description: Dodaje prosty moduł FAQ/Q&A z shortcode'em do wyświetlania listy pytań i odpowiedzi w formie akordeonu.
 * Version: 1.0.0
 * Author: ChatGPT
 * Text Domain: simple-qa-accordion
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Simple_QA_Accordion')) {
    class Simple_QA_Accordion
    {
        private const POST_TYPE = 'sqa_item';

        public function __construct()
        {
            add_action('init', [$this, 'load_textdomain']);
            add_action('init', [$this, 'register_post_type']);
            add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
            add_shortcode('simple_qa', [$this, 'render_shortcode']);
            add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
            add_action('save_post', [$this, 'save_post']);
            add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'register_columns']);
            add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_columns'], 10, 2);
        }

        public function load_textdomain(): void
        {
            load_plugin_textdomain(
                'simple-qa-accordion',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages'
            );
        }

        public function register_post_type(): void
        {
            $labels = [
                'name' => __('Pytania i odpowiedzi', 'simple-qa-accordion'),
                'singular_name' => __('Pytanie i odpowiedź', 'simple-qa-accordion'),
                'menu_name' => __('Q&A', 'simple-qa-accordion'),
                'add_new_item' => __('Dodaj nowe pytanie', 'simple-qa-accordion'),
                'edit_item' => __('Edytuj pytanie', 'simple-qa-accordion'),
                'new_item' => __('Nowe pytanie', 'simple-qa-accordion'),
                'view_item' => __('Zobacz pytanie', 'simple-qa-accordion'),
                'search_items' => __('Szukaj pytań', 'simple-qa-accordion'),
                'not_found' => __('Nie znaleziono pytań', 'simple-qa-accordion'),
                'not_found_in_trash' => __('Brak pytań w koszu', 'simple-qa-accordion'),
            ];

            $args = [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_icon' => 'dashicons-editor-help',
                'supports' => ['title', 'page-attributes'],
                'hierarchical' => false,
                'has_archive' => false,
                'rewrite' => false,
                'show_in_rest' => true,
            ];

            register_post_type(self::POST_TYPE, $args);
        }

        public function register_meta_boxes(): void
        {
            add_meta_box(
                'sqa_answer_box',
                __('Odpowiedź', 'simple-qa-accordion'),
                [$this, 'render_answer_editor'],
                self::POST_TYPE,
                'normal',
                'high'
            );
        }

        public function render_answer_editor($post): void
        {
            wp_nonce_field('sqa_save_post', 'sqa_nonce');
            echo '<p>' . esc_html__('Dodaj treść odpowiedzi. Tytuł posta będzie treścią pytania.', 'simple-qa-accordion') . '</p>';
            wp_editor(
                $post->post_content,
                'sqa_answer_editor',
                [
                    'textarea_name' => 'sqa_answer_content',
                    'media_buttons' => true,
                    'textarea_rows' => 8,
                ]
            );
        }

        public function save_post(int $post_id): void
        {
            if (!isset($_POST['post_type']) || $_POST['post_type'] !== self::POST_TYPE) {
                return;
            }

            if (wp_is_post_revision($post_id)) {
                return;
            }

            if (!isset($_POST['sqa_nonce']) || !wp_verify_nonce($_POST['sqa_nonce'], 'sqa_save_post')) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            if (isset($_POST['sqa_answer_content'])) {
                $content = wp_kses_post($_POST['sqa_answer_content']);
                remove_action('save_post', [$this, 'save_post']);
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $content,
                ]);
                add_action('save_post', [$this, 'save_post']);
            }
        }

        public function register_columns(array $columns): array
        {
            $columns['menu_order'] = __('Kolejność', 'simple-qa-accordion');
            return $columns;
        }

        public function render_columns(string $column, int $post_id): void
        {
            if ($column === 'menu_order') {
                echo (int) get_post_field('menu_order', $post_id);
            }
        }

        public function maybe_enqueue_assets(): void
        {
            if (!is_singular()) {
                return;
            }

            global $post;
            if (!$post || !has_shortcode($post->post_content, 'simple_qa')) {
                return;
            }

            $this->enqueue_assets();
        }

        private function enqueue_assets(): void
        {
            $plugin_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'sqa-styles',
                $plugin_url . 'assets/css/qa.css',
                [],
                '1.0.0'
            );

            wp_enqueue_script(
                'sqa-scripts',
                $plugin_url . 'assets/js/qa.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }

        public function render_shortcode($atts = []): string
        {
            $atts = shortcode_atts([
                'order' => 'ASC',
            ], $atts, 'simple_qa');

            $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query = new WP_Query([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => [
                    'menu_order' => $order,
                    'title' => $order,
                ],
                'order' => $order,
            ]);

            if (!$query->have_posts()) {
                return '<p>' . esc_html__('Brak pytań i odpowiedzi do wyświetlenia.', 'simple-qa-accordion') . '</p>';
            }

            ob_start();
            ?>
            <div class="sqa-list" role="list">
                <?php
                while ($query->have_posts()) {
                    $query->the_post();
                    $question = get_the_title();
                    $answer = apply_filters('the_content', get_the_content());
                    $item_id = 'sqa-item-' . get_the_ID();
                    ?>
                    <div class="sqa-item" role="listitem">
                        <button class="sqa-question" aria-expanded="false" aria-controls="<?php echo esc_attr($item_id); ?>">
                            <span class="sqa-icon" aria-hidden="true">+</span>
                            <span class="sqa-question-text"><?php echo esc_html($question); ?></span>
                        </button>
                        <div class="sqa-answer" id="<?php echo esc_attr($item_id); ?>" hidden>
                            <?php echo $answer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                    <?php
                }
                wp_reset_postdata();
                ?>
            </div>
            <?php
            return ob_get_clean();
        }
    }

    new Simple_QA_Accordion();
}
