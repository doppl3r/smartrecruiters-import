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

            $output = 'TODO: // Print list of jobs';

            // Add wrapper
            $output = '<div class="idx-sr-public jobs">' . $output .'</div>';

            return $output;
        }
    }
?>