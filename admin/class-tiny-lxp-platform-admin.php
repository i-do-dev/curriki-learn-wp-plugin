<?php
/*
 *  wordpress-tiny-lxp-platform - Enable WordPress to act as an Tiny LXP Platform.

 *  Copyright (C) 2022  Waqar Muneer
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: Waqar Muneer <waqarmuneer@gmail.com>
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-tiny-lxp-platform
 * @since      1.0.0
 * @package    Tiny_LXP_Platform
 * @subpackage Tiny_LXP_Platform/admin
 * @author     Waqar Muneer <waqarmuneer@gmail.com>
 */
use ceLTIc\LTI\Util;

class Tiny_LXP_Platform_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_scripts($hook)
    {
        if (($hook === 'post-new.php') || ($hook === 'post.php')) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/tiny-lxp-platform-post.css', array(), $this->version);
            wp_enqueue_script("script-ck-editor", 'https://cdn.ckeditor.com/4.20.1/full/ckeditor.js', array(), $this->version);
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/tiny-lxp-platform-post.js', array('wp-element', 'wp-editor', 'wp-rich-text'), $this->version, false);
        } elseif (($hook === "settings_page_{$this->plugin_name}-settings") || ($hook === "settings_page_{$this->plugin_name}-edit")) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/tiny-lxp-platform-settings.js');
        }
    }

    public function options_page()
    {
        $menu = add_options_page('Tiny LXP Tools', 'Tiny LXP Tools', 'manage_options', Tiny_LXP_Platform::get_plugin_name(),
            array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add Tiny LXP Tool', 'Add New', 'manage_options', Tiny_LXP_Platform::get_plugin_name() . '-edit',
            array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'Tiny LXP Platform Settings', 'Settings', 'manage_options',
            Tiny_LXP_Platform::get_plugin_name() . '-settings', array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
        add_menu_page(
            'Edlink Integrations',  // Page title
            'Edlink Settings',       // Menu title
            'manage_options',        // Capability required to access this menu
            'edlink_options',        // Menu slug
            'edlink_options_page'    // Function that displays the page content
        );

        // Display the settings page content
        function edlink_options_page() {
            ?>
            <form action="options.php" method="post">
                <h2>Edlink Integrations</h2>
                <?php
                settings_fields('edlink_options_group');  // Output security fields for the registered setting
                do_settings_sections('edlink_options');   // Output setting sections and fields
                submit_button('Save Settings');           // Save button
                ?>
            </form>
            <?php
        }

        // Callback function for the section description
        function edlink_settings_section_cb() {
            echo '<p>Configure your Edlink API credentials below.</p>';
        }

        // Render the Application Id field
        function edlink_application_id_render() {
            $options = get_option('edlink_options');
            ?>
            <input type="text" name="edlink_options[edlink_application_id]" value="<?php echo isset($options['edlink_application_id']) ? esc_attr($options['edlink_application_id']) : ''; ?>" />
            <?php
        }

        // Render the Application Secrets field
        function edlink_application_secrets_render() {
            $options = get_option('edlink_options');
            ?>
            <input type="text" name="edlink_options[edlink_application_secrets]" value="<?php echo isset($options['edlink_application_secrets']) ? esc_attr($options['edlink_application_secrets']) : ''; ?>" />
            <?php
        }

        // Render the Enable Edlink SSO checkbox field
        function edlink_sso_enable_render() {
            $options = get_option('edlink_options');
            ?>
            <input type="checkbox" name="edlink_options[edlink_sso_enable]" value="1" <?php checked(1, isset($options['edlink_sso_enable']) ? $options['edlink_sso_enable'] : 0, true); ?> />
            <label for="edlink_sso_enable">Enable Edlink SSO</label>
            <?php
        }

        function count_people_by_roles_from_edlink($access_token, $value) {
            $count = 0;
            $next = null;
            $api_url = 'https://ed.link/api/v2/graph/people';

            do {
                // Construct URL with pagination and $first
                // Build the query parameters
                $params = array(
                    '$filter' => json_encode(array(
                        'roles' => array(
                            array(
                                'operator' => 'equals',
                                'value' => $value
                            )
                        )
                    )),
                    '$first' => 10000
                );

                // Add the query parameters to the URL
                $url = add_query_arg($params, $api_url);
                
                //$url = $api_url . '?$first=' . $first . '&$filter=' .$filter;
                if ($next) {
                    // Parse the URL to extract the query part
                    $parsed_url = parse_url($next);

                    // Extract the query string part and parse it into an array
                    $query_string = $parsed_url['query'];

                    parse_str($query_string, $query_params);

                    // Get the value of $cursor
                    $cursor_value = isset($query_params['$cursor']) ? $query_params['$cursor'] : null;
                    $url .= '&cursor=' . $cursor_value;            
                }

                // Prepare request headers
                $args = array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $access_token
                    )
                );

                // Make API request
                $response = wp_remote_get( $url, $args );

                if ( is_wp_error( $response ) ) {
                    return 'Error: ' . $response->get_error_message().'<br/> Please refresh the page again!';
                }

                // Parse the response
                $data = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( !empty( $data['$data'] ) ) {
                    // Count current page's $data
                    $count += count( $data['$data'] );
                }

                // Update next page token for the loop
                $next = isset($data['$next']) ? $data['$next'] : null;

            } while ( $next );  // Continue loop until there are no more pages

            // Return total count of people    
            return $count;
        }

        function count_schools_from_edlink($access_token, $value) {
            $count = 0;
            $next = null;
            $api_url = 'https://ed.link/api/v2/graph/schools';

            do {
                // Construct URL with pagination and $first
                // Build the query parameters
                $params = array(            
                    '$first' => 10000
                );

                // Add the query parameters to the URL
                $url = add_query_arg($params, $api_url);
                
                //$url = $api_url . '?$first=' . $first . '&$filter=' .$filter;
                if ($next) {
                    // Parse the URL to extract the query part
                    $parsed_url = parse_url($next);

                    // Extract the query string part and parse it into an array
                    $query_string = $parsed_url['query'];

                    parse_str($query_string, $query_params);

                    // Get the value of $cursor
                    $cursor_value = isset($query_params['$cursor']) ? $query_params['$cursor'] : null;
                    $url .= '&cursor=' . $cursor_value;            
                }

                // Prepare request headers
                $args = array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $access_token
                    )
                );

                // Make API request
                $response = wp_remote_get( $url, $args );

                if ( is_wp_error( $response ) ) {
                    return 'Error: ' . $response->get_error_message().'<br/> Please refresh the page again!';
                }

                // Parse the response
                $data = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( !empty( $data['$data'] ) ) {
                    // Count current page's $data
                    $count += count( $data['$data'] );
                }

                // Update next page token for the loop
                $next = isset($data['$next']) ? $data['$next'] : null;

            } while ( $next );  // Continue loop until there are no more pages

            // Return total count of people    
            return $count;
        }

        function get_schools_list_from_edlink($access_token) {
            $api_url = 'https://ed.link/api/v2/graph/schools';
            $error = '';
            $data = [];
            // Prepare request headers
            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                )
            );

            // Make API request
            $response = wp_remote_get( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                $error = 'Error: ' . $response->get_error_message().'<br/> Please refresh the page again!';
            }

            // Parse the response
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( !empty( $data['$data'] ) ) {        
                $data = $data['$data'];
            }
            return array('error' => $error, 'schools' => $data);   
        }
    }

    public function network_options_page()
    {
        $menu = add_submenu_page('settings.php', 'Network Tiny LXP Tools', 'Network Tiny LXP Tools', 'manage_options',
            Tiny_LXP_Platform::get_plugin_name(), array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add Network Tiny LXP Tool', 'Add New', 'manage_options',
            Tiny_LXP_Platform::get_plugin_name() . '-edit', array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'Network Tiny LXP Platform Settings', 'Network Settings', 'manage_options',
            Tiny_LXP_Platform::get_plugin_name() . '-settings', array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
        add_action('network_admin_edit_' . Tiny_LXP_Platform::get_plugin_name() . '-settings', array($this, 'save_network_options'));
    }

    public function save_network_options()
    {
        $rawoptions = $_POST[Tiny_LXP_Platform::get_settings_name()];
        $options = array();
        foreach ($rawoptions as $option => $value) {
            $option = sanitize_text_field($option);
            switch ($option) {
                case 'privatekey':
                    $value = sanitize_textarea_field($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            $options[$option] = $value;
        }
        if (is_multisite()) {
            update_site_option(Tiny_LXP_Platform::get_settings_name(), $options);
        } else {
            update_option(Tiny_LXP_Platform::get_settings_name(), $options);
        }
        add_action('all_admin_notices', 'save_network_notice_success');
        wp_redirect('settings.php?page=' . Tiny_LXP_Platform::get_plugin_name() . '-settings');
        exit;
    }

    public function save_network_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Settings updated.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function load_tools_table()
    {
        $screen = get_current_screen();
        add_filter("manage_{$screen->id}_columns", array('Tiny_LXP_Platform_Tool_List_Table', 'define_columns'), 10, 0);
        add_screen_option('per_page',
            array('label' => __('Tools', Tiny_LXP_Platform::get_plugin_name()), 'default' => 5, 'option' => Tiny_LXP_Platform::get_plugin_name() . '-tool_per_page'));
        $screen->add_help_tab(array(
            'id' => Tiny_LXP_Platform::get_plugin_name() . '-display',
            'title' => __('Screen Display', Tiny_LXP_Platform::get_plugin_name()),
            'content' => '<p>' . __('You can select which columns to display and the number of Tiny LXP Tools to list per screen using the Screen Options tab.',
                Tiny_LXP_Platform::get_plugin_name()) . '</p>'
        ));
    }

    public function load_submenu()
    {
        add_filter('submenu_file', array($this, 'submenu_file'), 10, 2);
    }

    function submenu_file($submenu_file, $parent_file)
    {
// Ensure plugin remains highlighted as current in menu
        if ((($parent_file === 'options-general.php') || ($parent_file === 'settings.php')) && empty($submenu_file)) {
            $submenu_file = Tiny_LXP_Platform::get_plugin_name();
        }

        return $submenu_file;
    }

    public function settings_init()
    {
        register_setting(Tiny_LXP_Platform::get_plugin_name(), Tiny_LXP_Platform::get_settings_name());
        $options = Tiny_LXP_Platform_Tool::getOptions();

        add_settings_section(
            'section_general', __('General Settings', Tiny_LXP_Platform::get_plugin_name()), array($this, 'section_general'),
            Tiny_LXP_Platform::get_plugin_name()
        );
        add_settings_field('field_debug', __('Debug mode?', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_debug', 'name' => 'debug', 'options' => $options));
        add_settings_field('field_uninstall', __('Delete data on uninstall?', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), Tiny_LXP_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_uninstall', 'name' => 'uninstall', 'options' => $options));
        add_settings_field('field_platformguid', __('Platform GUID', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_text'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_platformguid', 'name' => 'platformguid', 'options' => $options));

        add_settings_section(
            'section_privacy', __('Privacy Settings', Tiny_LXP_Platform::get_plugin_name()), array($this, 'section_privacy'),
            Tiny_LXP_Platform::get_plugin_name()
        );
        add_settings_field('field_name', __('Send user\'s name?', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_sendusername', 'name' => 'sendusername', 'options' => $options));
        add_settings_field('field_id', __('Send user\'s ID?', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserid', 'name' => 'senduserid', 'options' => $options));
        add_settings_field('field_email', __('Send user\'s email?', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), Tiny_LXP_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduseremail', 'name' => 'senduseremail', 'options' => $options));
        add_settings_field('field_role', __('Send user\'s role?', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserrole', 'name' => 'senduserrole', 'options' => $options));
        add_settings_field('field_username', __('Send user\'s username?', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), Tiny_LXP_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserusername', 'name' => 'senduserusername', 'options' => $options));

        add_settings_section(
            'section_presentation', __('Presentation Settings', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'section_presentation'), Tiny_LXP_Platform::get_plugin_name()
        );
        add_settings_field('field_target', __('Presentation target', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_target'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationtarget', 'name' => 'presentationtarget', 'options' => $options));
        add_settings_field('field_width', __('Width of pop-up window or iframe', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'field_text'), Tiny_LXP_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationwidth', 'name' => 'presentationwidth', 'options' => $options));
        add_settings_field('field_height', __('Height of pop-up window or iframe', Tiny_LXP_Platform::get_plugin_name()),
            array($this, 'field_text'), Tiny_LXP_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationheight', 'name' => 'presentationheight', 'options' => $options));

        add_settings_section(
            'section_security', __('Security Settings', Tiny_LXP_Platform::get_plugin_name()), array($this, 'section_security'),
            Tiny_LXP_Platform::get_plugin_name()
        );
        add_settings_field('field_kid', __('Key ID', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_text'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_security',
            array('class' => 'row', 'label_for' => 'id_kid', 'name' => 'kid', 'options' => $options));
        add_settings_field('field_privatekey', __('Private key', Tiny_LXP_Platform::get_plugin_name()), array($this, 'field_textarea'),
            Tiny_LXP_Platform::get_plugin_name(), 'section_security',
            array('class' => 'row', 'label_for' => 'id_privatekey', 'name' => 'privatekey', 'options' => $options));
        // Register a new setting for 'edlink' options
        register_setting('edlink_options_group', 'edlink_options');
        
        // Add a new section for the settings page
        add_settings_section(
            'edlink_settings_section',      // Section ID
            'Edlink API Settings',          // Section Title
            'edlink_settings_section_cb',   // Callback function to explain the section
            'edlink_options'                // Page slug where the section will appear
        );
        
        // Add individual fields (Application ID, Secret, etc.) to the section
        add_settings_field(
            'edlink_application_id',               // Field ID
            'Application ID',                      // Field Title
            'edlink_application_id_render',        // Callback function to render the field
            'edlink_options',               // Page slug where the field will appear
            'edlink_settings_section'       // Section ID
        );
        
        add_settings_field(
            'edlink_application_secrets',
            'Application Secrets',
            'edlink_application_secrets_render',
            'edlink_options',
            'edlink_settings_section'
        );

        // Add a new checkbox field for 'edlink_sso_enable'
        add_settings_field(
            'edlink_sso_enable',
            'Enable Edlink SSO',
            'edlink_sso_enable_render',
            'edlink_options',
            'edlink_settings_section'
        );
    }

    public function section_general()
    {

    }

    public function section_privacy()
    {

    }

    public function section_presentation()
    {

    }

    public function section_security()
    {

    }

    public function field_checkbox($args)
    {
        echo('<input id="' . esc_attr($args['label_for']) . '" type="checkbox" aria-required="false" value="true" name="' . Tiny_LXP_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']"' .
        checked(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'true'), true, false) . '>' . "\n");
    }

    public function field_text($args)
    {
        echo('<input id="' . esc_attr($args['label_for']) . '" type="text" aria-required="false" value="');
        if (isset($args['options'][$args['name']])) {
            echo(esc_attr($args['options'][$args['name']]));
        }
        echo('" name="' . Tiny_LXP_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']">' . "\n");
    }

    public function field_textarea($args)
    {
        echo('<textarea id=" ' . esc_attr($args['label_for']) . '" name="' . Tiny_LXP_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']" class="regular-text">');
        if (isset($args['options'][$args['name']])) {
            echo($args['options'][$args['name']]);
        }
        echo('</textarea>' . "\n");
    }

    public function field_target($args)
    {
        echo('<select id="' . esc_attr($args['label_for']) . '" name="' . Tiny_LXP_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']">' . "\n");
        echo('  <option value="window"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'window'),
            true, false) . '>New window</option>' . "\n");
        echo('  <option value="popup"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'popup'),
            true, false) . '>Pop-up window</option>' . "\n");
        echo('  <option value="iframe"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'iframe'),
            true, false) . '>iFrame</option>' . "\n");
        echo('  <option value="embed"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'embed'),
            true, false) . '>Embed</option>' . "\n");
        echo('</select>' . "\n");
    }

    public function options_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        settings_errors(Tiny_LXP_Platform::get_plugin_name() . '_messages');

        require_once(plugin_dir_path(dirname(__FILE__)) . 'admin/partials/tiny-lxp-platform-admin-settings.php');
    }

    public function view_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/tiny-lxp-platform-admin-view.php';
    }

    public function edit_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (empty($_REQUEST['tool'])) {
            $tool = new Tiny_LXP_Platform_Tool(Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        } else {
            $tool = Tiny_LXP_Platform_Tool::fromRecordId(intval(sanitize_text_field($_REQUEST['tool'])),
                    Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], Tiny_LXP_Platform::get_plugin_name() . '-nonce')) {
                add_action('all_admin_notices', array($this, 'error_update'));
            } else {
                $this->update_tool($tool);
            }
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/tiny-lxp-platform-admin-edit.php';
    }

    function error_update()
    {
        $allowed = array('em' => array());
        echo('  <div class="notice notice-error">' . "\n");
        echo('    <p>' . esc_html__('Unable to save the changes.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('  </div>' . "\n");
    }

    private function update_tool($tool)
    {
        $tool->name = sanitize_text_field($_POST['name']);
        $tool->code = sanitize_text_field($_POST['code']);
        $tool->enabled = !empty($_POST['enabled']) && (sanitize_text_field($_POST['enabled']) === 'true');
        $tool->debugMode = !empty($_POST['debugmode']) && (sanitize_text_field($_POST['debugmode']) === 'true');
        $tool->setSetting('sendUserName',
            (!empty($_POST['sendusername']) && (sanitize_text_field($_POST['sendusername']) === 'true')) ? 'true' : null);
        $tool->setSetting('sendUserId',
            (!empty($_POST['senduserid']) && (sanitize_text_field($_POST['senduserid'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserEmail',
            (!empty($_POST['senduseremail']) && (sanitize_text_field($_POST['senduseremail'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserRole',
            (!empty($_POST['senduserrole']) && (sanitize_text_field($_POST['senduserrole'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserUsername',
            (!empty($_POST['senduserusername']) && (sanitize_text_field($_POST['senduserusername'] === 'true'))) ? 'true' : null);
        $tool->setSetting('presentationTarget', sanitize_text_field($_POST['presentationtarget']));
        $tool->setSetting('presentationWidth', sanitize_text_field($_POST['presentationwidth']));
        $tool->setSetting('presentationHeight', sanitize_text_field($_POST['presentationheight']));
        $tool->messageUrl = esc_url_raw($_POST['messageurl']);
        $tool->useContentItem = !empty($_POST['usecontentitem']) && (sanitize_text_field($_POST['usecontentitem']) === 'true');
        $tool->contentItemUrl = esc_url_raw($_POST['contentitemurl']);
        $tool->setSetting('custom', str_replace("\r\n", '&#13;&#10;', sanitize_textarea_field($_POST['custom'])));
        $tool->setKey(sanitize_text_field($_POST['consumerkey']));
        $tool->secret = sanitize_text_field($_POST['sharedsecret']);
        $tool->initiateLoginUrl = sanitize_text_field($_POST['initiateloginurl']);
        $redirectionUris = trim(sanitize_textarea_field($_POST['redirectionuris']));
        if (!empty($redirectionUris)) {
            $tool->redirectionUris = explode("\r\n", sanitize_textarea_field($_POST['redirectionuris']));
        } else {
            $tool->redirectionUris = array();
        }
        $tool->jku = sanitize_text_field($_POST['jwksurl']);
        $tool->rsaKey = sanitize_textarea_field($_POST['publickey']);
        if (empty($tool->initiateLoginUrl) || empty($tool->redirectionUris)) {
            $tool->ltiVersion = Util::LTI_VERSION1;
            $tool->signatureMethod = 'HMAC-SHA1';
        } else {
            $tool->ltiVersion = Util::LTI_VERSION1P3;
            $tool->signatureMethod = 'RS256';
        }
        $tool->save();
    }

}
