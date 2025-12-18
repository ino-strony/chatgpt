<?php
/**
 * Plugin Name: Classic Q&A Accordion
 * Description: Prosty plugin Q&A z krótkim kodem do wyświetlania pytań i odpowiedzi w klasycznym akordeonie.
 * Version: 1.0.0
 * Author: ChatGPT
 * Text Domain: classic-qa-accordion
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Classic_QA_Accordion')) {
    class Classic_QA_Accordion
    {
        private const POST_TYPE = 'cqa_item';

        public function __construct()
        {
            add_action('init', [$this, 'load_textdomain']);
            add_action('init', [$this, 'register_post_type']);
            add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
            add_action('save_post', [$this, 'save_post']);
            add_shortcode('classic_qa', [$this, 'render_shortcode']);
            add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        }

        public function load_textdomain(): void
        {
            load_plugin_textdomain(
                'classic-qa-accordion',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages'
            );
        }

        public function register_post_type(): void
        {
            $labels = [
                'name' => __('Pytania i odpowiedzi', 'classic-qa-accordion'),
                'singular_name' => __('Pytanie i odpowiedź', 'classic-qa-accordion'),
                'menu_name' => __('Q&A', 'classic-qa-accordion'),
                'add_new_item' => __('Dodaj nowe pytanie', 'classic-qa-accordion'),
                'edit_item' => __('Edytuj pytanie', 'classic-qa-accordion'),
                'new_item' => __('Nowe pytanie', 'classic-qa-accordion'),
                'view_item' => __('Zobacz pytanie', 'classic-qa-accordion'),
                'search_items' => __('Szukaj pytań', 'classic-qa-accordion'),
                'not_found' => __('Nie znaleziono pytań', 'classic-qa-accordion'),
                'not_found_in_trash' => __('Brak pytań w koszu', 'classic-qa-accordion'),
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
                'cqa_answer_box',
                __('Odpowiedź', 'classic-qa-accordion'),
                [$this, 'render_answer_editor'],
                self::POST_TYPE,
                'normal',
                'high'
            );
        }

        public function render_answer_editor($post): void
        {
            wp_nonce_field('cqa_save_post', 'cqa_nonce');
            echo '<p>' . esc_html__('Dodaj treść odpowiedzi. Tytuł posta będzie treścią pytania.', 'classic-qa-accordion') . '</p>';
            wp_editor(
                $post->post_content,
                'cqa_answer_editor',
                [
                    'textarea_name' => 'cqa_answer_content',
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

            if (!isset($_POST['cqa_nonce']) || !wp_verify_nonce($_POST['cqa_nonce'], 'cqa_save_post')) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            if (isset($_POST['cqa_answer_content'])) {
                $content = wp_kses_post($_POST['cqa_answer_content']);
                remove_action('save_post', [$this, 'save_post']);
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $content,
                ]);
                add_action('save_post', [$this, 'save_post']);
            }
        }

        public function maybe_enqueue_assets(): void
        {
            if (!is_singular()) {
                return;
            }

            global $post;
            if (!$post || !has_shortcode($post->post_content, 'classic_qa')) {
                return;
            }

            $this->enqueue_assets();
        }

        private function enqueue_assets(): void
        {
            $plugin_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'cqa-styles',
                $plugin_url . 'classic-qa/assets/css/qa.css',
                [],
                '1.0.0'
            );

            wp_enqueue_script(
                'cqa-scripts',
                $plugin_url . 'classic-qa/assets/js/qa.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }

        public function render_shortcode($atts = []): string
        {
            $atts = shortcode_atts([
                'order' => 'ASC',
            ], $atts, 'classic_qa');

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
                return '<p>' . esc_html__('Brak pytań i odpowiedzi do wyświetlenia.', 'classic-qa-accordion') . '</p>';
            }

            ob_start();
            ?>
            <div class="cqa-list" role="list">
                <?php
                while ($query->have_posts()) {
                    $query->the_post();
                    $question = get_the_title();
                    $answer = apply_filters('the_content', get_the_content());
                    $item_id = 'cqa-item-' . get_the_ID();
                    ?>
                    <div class="cqa-item" role="listitem">
                        <button class="cqa-question" aria-expanded="false" aria-controls="<?php echo esc_attr($item_id); ?>">
                            <span class="cqa-icon" aria-hidden="true">+</span>
                            <span class="cqa-question-text"><?php echo esc_html($question); ?></span>
                        </button>
                        <div class="cqa-answer" id="<?php echo esc_attr($item_id); ?>" hidden>
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

    new Classic_QA_Accordion();
}
