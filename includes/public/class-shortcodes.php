<?php
    class IDX_SmartRecruiters_Shortcodes {
        public function __construct() {
            add_action('init', array($this, 'add_shortcodes'));
        }

        public function add_shortcodes() {
            add_shortcode('jobs', array($this, 'shortcode'));
        }

        public function shortcode($atts, $content = null) {
            $atts = shortcode_atts(array(
                'type' => null,
                'data' => null
            ), $atts);

            // Enqueue search script/styles
            wp_enqueue_script('search');
            wp_enqueue_style('search');

            // Render default search form
            ob_start();
            include('form-search.php');
            $output = ob_get_clean();

            // Return output value
            return $output;
        }
    }
?>