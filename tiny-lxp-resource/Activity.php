<?php

use ceLTIc\LTI;

class Activity
{

    public static $toolUrl = null;

    public static function validate($messageUrl, $targetLinkUri)
    {

        if ($messageUrl !== $targetLinkUri) {
            self::$toolUrl =  $targetLinkUri;
            return true;
        } else {
            return false;
        }
    }

    private static function parse_custom_parameters($customparams)
    {
        $custom = array();
        $params = null;
        if (!empty($customparams)) {
            parse_str(str_replace('&#13;&#10;', '&', $customparams), $custom);
            foreach ($custom as $name => $value) {
                $name = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name)));
                if (!empty($name)) {
                    $params["custom_{$name}"] = $value;
                }
            }
        }
        return $params;
    }

    private static function check_content_type($contenturl)
    {
        if (str_contains($contenturl['path'], '/mod/curriki') && str_contains($contenturl['query'], 'activity=')) {
            parse_str($contenturl['query'], $params);
            $contentpublicurl = "/lti-tools/activity/" . $params['activity'];
        }

        return $contentpublicurl;
    }

    public static function is_public()
    {
        if ( !isset($_GET['post'])) {
            die("Unable to access resource. Please check your browser configuration to allow Cross Site Request, or contact site administrator.");
        }
        $post = get_post(intval(sanitize_text_field($_GET['post'])));
        $post_tiny_lxp_tool = get_post_meta($post->ID, 'lti_tool_code')[0];
        $tool = Tiny_LXP_Platform_Tool::fromCode($post_tiny_lxp_tool, Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        $post_tiny_lxy_custom_attr = explode('=', get_post_meta($post->ID, 'lti_custom_attr')[0]);
        $customparams = self::parse_custom_parameters($tool->getSetting('custom'));
        $link_atts = Tiny_LXP_Platform_Public::get_link_atts($post, sanitize_text_field($_GET['id']));
        
        if (isset($customparams["custom_currikisite"])) {
            $curriki_site = $customparams["custom_currikisite"];
            $tiny_lxp_content_url = null;
            if (in_array('activity', $post_tiny_lxy_custom_attr)) {
                $queryParamArr = array();
                $params_data = '';
                if (isset($_GET['slideNumber'])) {
                    $queryParamArr['slideNumber'] = $_GET['slideNumber'];
                }
                $params_data = http_build_query($queryParamArr);
                $params_data = strlen($params_data) > 0 ? "?$params_data" : '';
                $tiny_lxp_content_url = $curriki_site . "/lti-tools/activity/" . $post_tiny_lxy_custom_attr[array_search("activity", $post_tiny_lxy_custom_attr) + 1] . $params_data;
            }
            
            if ($tiny_lxp_content_url) {

            $page = '<html>
                        <head>
                            <title>1EdTech Tiny LXP message</title>
                        </head>
                        <body>
                            <iframe style="border: none; overflow: scroll;" width="100%" height="100%" src="'.$tiny_lxp_content_url.'" allowfullscreen="true"></iframe>
                        </body>
                    </html>';
            echo $page;
            exit;
            } else {
                echo '';
                exit;
            }
        } else {
            echo '';
            exit;
        }

    }
}
