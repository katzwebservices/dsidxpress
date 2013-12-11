<?php
class dsWidgets_Service_Base {
    static $widgets_api_stub = 'http://api-b.idx.diversesolutions.com/api/';
    static $widgets_admin_api_stub = 'http://api-b.idx.diversesolutions.com/api/';
    static $widgets_images_stub = 'http://widgets.diverse-cdn.com/Content/Images/widgets';
    static $widgets_cdn = 'http://widgets.diverse-cdn.com';
    //static $widgets_images_stub = '/wp-content/mu-plugins/dsidxwidgets/images';
    static function get_random_string($valid_chars, $length)
    {
        $random_string = "";
        $num_valid_chars = strlen($valid_chars);
        for ($i = 0; $i < $length; $i++)
        {
            $random_pick = mt_rand(1, $num_valid_chars);
            $random_char = $valid_chars[$random_pick-1];
            $random_string .= $random_char;
        }
        return $random_string;
    }
}
?>
