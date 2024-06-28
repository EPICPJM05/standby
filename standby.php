<?php
/**
 * @package SiteStandby
 */
/*
Plugin Name: Site Standby
Description: Easily enable a customizable maintenance mode page for your WordPress site. Keep visitors informed with personalized messages and designs while you update your website.
Version: 1.0.1
Author: PJM
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SiteStandby {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('template_redirect', array($this, 'activate_maintenance_mode'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Site Standby',
            'Site Standby',
            'manage_options',
            'site_standby',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('pluginPage', 'site_standby_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        add_settings_section(
            'site_standby_pluginPage_section',
            __('Settings', 'site-standby'),
            array($this, 'settings_section_callback'),
            'pluginPage'
        );

        add_settings_field(
            'site_standby_text_field',
            __('Custom Message<br>(Default)', 'site-standby'),
            array($this, 'text_field_render'),
            'pluginPage',
            'site_standby_pluginPage_section'
        );

        add_settings_field(
            'site_standby_color_field',
            __('Background Color<br>(Default)', 'site-standby'),
            array($this, 'color_field_render'),
            'pluginPage',
            'site_standby_pluginPage_section'
        );

        add_settings_field(
            'site_standby_custom_html_field',
            __('Custom HTML Content<p>(Do not write if you want to see default page)</p>', 'site-standby'),
            array($this, 'custom_html_field_render'),
            'pluginPage',
            'site_standby_pluginPage_section'
        );

        add_settings_field(
            'site_standby_custom_css_field',
            __('Custom CSS<p>(Optional)</p>', 'site-standby'),
            array($this, 'custom_css_field_render'),
            'pluginPage',
            'site_standby_pluginPage_section'
        );
    }

    public function sanitize_settings($input) {
        $sanitized_input = array();
        if (isset($input['site_standby_text_field'])) {
            $sanitized_input['site_standby_text_field'] = sanitize_text_field($input['site_standby_text_field']);
        }
        if (isset($input['site_standby_color_field'])) {
            $sanitized_input['site_standby_color_field'] = sanitize_hex_color($input['site_standby_color_field']);
        }
        if (isset($input['site_standby_custom_html_field'])) {
            $sanitized_input['site_standby_custom_html_field'] = wp_kses_post($input['site_standby_custom_html_field']);
        }
        if (isset($input['site_standby_custom_css_field'])) {
            $sanitized_input['site_standby_custom_css_field'] = wp_strip_all_tags($input['site_standby_custom_css_field']);
        }
        return $sanitized_input;
    }

    public function text_field_render() {
        $options = get_option('site_standby_settings');
        ?>
        <input type='text' name='site_standby_settings[site_standby_text_field]' placeholder='<?php echo esc_attr__("We're undergoing maintenance. Please check back later.", 'site-standby'); ?>' value='<?php echo isset($options['site_standby_text_field']) ? esc_attr($options['site_standby_text_field']) : ''; ?>'>
        <?php
    }

    public function color_field_render() {
        $options = get_option('site_standby_settings');
        ?>
        <input type='color' name='site_standby_settings[site_standby_color_field]' value='<?php echo isset($options['site_standby_color_field']) ? esc_attr($options['site_standby_color_field']) : '#000000'; ?>'>
        <?php
    }

    public function custom_html_field_render() {
        $options = get_option('site_standby_settings');
        ?>
        <textarea name='site_standby_settings[site_standby_custom_html_field]' rows='10' cols='50' placeholder='<?php echo esc_attr__("<h1>Maintenance</h1><p>Our site is currently undergoing maintenance. Please check back soon.</p>", 'site-standby'); ?>'><?php echo isset($options['site_standby_custom_html_field']) ? esc_textarea($options['site_standby_custom_html_field']) : ''; ?></textarea>
        <?php
    }

    public function custom_css_field_render() {
        $options = get_option('site_standby_settings');
        ?>
        <textarea name='site_standby_settings[site_standby_custom_css_field]' rows='10' cols='50' placeholder='<?php echo esc_attr__('body { font-family: Arial, sans-serif; }', 'site-standby'); ?>'><?php echo isset($options['site_standby_custom_css_field']) ? esc_textarea($options['site_standby_custom_css_field']) : ''; ?></textarea>
        <?php
    }

    public function settings_section_callback() {
        echo esc_html__('Customize your maintenance mode settings below:', 'site-standby');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('pluginPage');
                do_settings_sections('pluginPage');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function activate_maintenance_mode() {
        if (!current_user_can('administrator')) {
            // Allow admins to access the site
            ob_clean();

            $options = get_option('site_standby_settings');
            $custom_html = isset($options['site_standby_custom_html_field']) ? $options['site_standby_custom_html_field'] : '';
            $background_color = isset($options['site_standby_color_field']) ? esc_attr($options['site_standby_color_field']) : '#000000';
            $custom_css = isset($options['site_standby_custom_css_field']) ? $options['site_standby_custom_css_field'] : '';
            $message = isset($options['site_standby_text_field']) && !empty($options['site_standby_text_field']) ? esc_html($options['site_standby_text_field']) : esc_html__('Our site is currently undergoing scheduled maintenance. Please try again later.', 'site-standby');

            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . esc_html__('Maintenance Mode', 'site-standby') . '</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                    }
                    body {
                        background-color: ' . esc_attr($background_color) . ';
                        color: white;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                        font-family: Arial, sans-serif;
                    }
                    h1 {
                        font-weight: bold;
                        font-size: xx-large;
                        text-align: center;
                    }
                    ' . esc_html($custom_css) . '
                </style>
            </head>
            <body>';

            if (!empty($custom_html)) {
                echo wp_kses_post($custom_html); // Escape custom HTML content.
            } else {
                echo '<h1>' . esc_html($message) . '</h1>';
            }

            echo '</body>
            </html>';

            exit;
        }
    }
}

new SiteStandby();
?>
