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
 * Define the Tiny LXP Platform class.
 *
 * Processes incoming Tiny LXP messages to the platform.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-tiny-lxp-platform
 * @since      1.0.0
 * @package    Tiny_LXP_Platform
 * @subpackage Tiny_LXP_Platform/includes
 * @author     Waqar Muneer <waqarmuneer@gmail.com>
 */
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Content;
use ceLTIc\LTI\Util;

class Tiny_LXP_Platform_Platform extends Platform
{

    public $contentItem = null;

    /**
     * Save the hint and message parameters when sending an initiate login request.
     *
     * @param string   $url               The message URL
     * @param string   $loginHint         The ID of the user
     * @param string   $tinyLxpMessageHint    The message hint being sent to the tool
     * @param array    $params            An associative array of message parameters
     */
    protected function onInitiateLogin(&$url, &$loginHint, &$tinyLxpMessageHint, $params)
    {
        $user = wp_get_current_user();
        $data = array(
            'login_hint' => $loginHint,
            'lti_message_hint' => $tinyLxpMessageHint,
            'params' => $params
        );
        update_user_option($user->ID, Tiny_LXP_Platform::get_plugin_name() . '-login', $data);
    }

    /**
     * Check the hint and recover the message parameters.
     */
    protected function onAuthenticate()
    {
        $user = wp_get_current_user();
        $login = get_user_option(Tiny_LXP_Platform::get_plugin_name() . '-login');
        update_user_option($user->ID, Tiny_LXP_Platform::get_plugin_name() . '-login', null);
        $parameters = Util::getRequestParameters();
        if ($parameters['login_hint'] !== $login['login_hint'] ||
            (isset($login['lti_message_hint']) && (!isset($parameters['lti_message_hint']) || ($parameters['lti_message_hint'] !== $login['lti_message_hint'])))) {
            $this->ok = false;
            $this->messageParameters['error'] = 'access_denied';
        } else {
            $this->messageParameters = $login['params'];
        }
    }

    /**
     * Process a valid content-item message
     */
    protected function onContentItem()
    {
        $this->ok = false;
        $items = Content\Item::fromJson(json_decode($this->messageParameters['content_items']));
        if (empty($items)) {
            $this->reason = 'No items returned';
        } elseif (count($items) > 1) {
            $this->reason = 'More than one item has been returned';
        } elseif (!$items[0] instanceof Content\LtiLinkItem) {
            $this->reason = 'Item must be an Tiny LXP link or assignment';
        } else {
            $this->ok = true;
            $this->contentItem = $items[0]->toJsonldObject();
        }
    }

}
