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

/*
  Plugin Name: Tiny Lxp
  Plugin URI: https://github.com/i-do-dev/TinyLxp-wp-plugin
  Text Domain: TinyLxp-wp-plugin
  Description: This plugin allows WordPress to act as a Platform using the IMS Learning Tools Interoperability (Tiny LXP) specification.
  Version: 2.0.3
  Author: Waqar Muneer
  Author URI: https://github.com/i-do-dev/TinyLxp-wp-plugin
  License: GPL3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin name.
 */
define('Tiny_LXP_PLATFORM_NAME', 'lti-platform');

/**
 * Current plugin version.
 */
define('Tiny_LXP_PLATFORM_VERSION', '2.0.3');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-tiny-lxp-platform.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tiny_lxp_platform()
{
    $plugin = new Tiny_LXP_Platform();
    if ($plugin->isOK()) {
        $plugin->run();
    }
}

run_tiny_lxp_platform();

register_activation_hook(__FILE__, 'on_activate');

function on_activate() {
    global $wpdb;

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tiny_lms_grades(
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        lesson_id bigint(20) default NULL,
        score FLOAT default NULL,
        user_id bigint(20) default NULL,
        PRIMARY KEY (id)
    )");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lxp_workbook_submissions(
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        lesson_id bigint(20) unsigned NOT NULL,
        course_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        fields longtext NOT NULL,
        submitted_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY lesson_user (lesson_id, user_id),
        KEY course_id (course_id),
        KEY user_id (user_id)
    )");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lxp_capstone_submissions(
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        lesson_id bigint(20) unsigned NOT NULL,
        course_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        response longtext NOT NULL,
        submitted_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY lesson_user (lesson_id, user_id),
        KEY course_id (course_id),
        KEY user_id (user_id)
    )");

    // Check if the pages already exist to avoid duplication
    $pagesArray = array(
    	['title' => 'Assignment','content' =>''],
    	['title' => 'Assignments','content' =>''],
		['title' => 'Calendar','content' =>''],
        ['title' => 'Classes','content' =>''],
		['title' => 'Courses','content' =>''],
		['title' => 'Dashboard','content' =>''],
		['title' => 'Districts','content' =>''],
		['title' => 'Grade Assignment','content' =>''],
		['title' => 'Grade Summary','content' =>''],
		['title' => 'Grades','content' =>''],
		['title' => 'Groups','content' =>''],
        ['title' => 'Lessons','content' =>''],
		['title' => 'Login','content' =>''],
		['title' => 'Sample Page','content' =>''],
		['title' => 'Schools','content' =>''],
		['title' => 'Search','content' =>''],
		['title' => 'Students','content' =>''],
		['title' => 'Teachers','content' =>''],
		['title' => 'Capstone Journal','content' =>'']
    );

    foreach ($pagesArray as $newPage) {
        $pageExist = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title = %s AND post_type = 'page' AND post_status = 'publish' ", $newPage['title']));

        if (!$pageExist) {
            // Page does not exist, create it
            $page = array(
                'post_title'    => $newPage['title'],
                'post_content'  => $newPage['content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
            );

            wp_insert_post($page);
        }
    }
}