<?php
/**
 * This class defines helper code that is used at admin partials
 *
 * @since      1.0.0
 * @package    Ibram_Tainacan_Helper
 * @subpackage Ibram_Tainacan/admin/partials
 * @author     Rodrigo de Oliveira <emaildorodrigolg@gmail.com>
 */
class IBRAM_Tainacan_Helper extends Ibram_Tainacan {

    public $special_collections = [ 'bem_permanente', 'bibliografico', 'arquivistico', 'descarte', 'desaparecimento', 'temporario'];

    public function get_selected_collection($key) {
        $ibram_opts = get_option($this->plugin_name);
        $selected = 0;

        if( $ibram_opts && is_array($ibram_opts) ) {
            if(array_key_exists($key, $ibram_opts)) {
                $selected = $ibram_opts[$key];
            }
        }

        return $selected;
    }

    public function get_selected_opts($post_type, $selected = 0) {
        $post_type_object = get_post_type_object($post_type);
        $label = $post_type_object->label;
        $posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));
        foreach ($posts as $post) {
            echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
        }
    }

    public function set_divider($index) {
        if(2 === $index) {
            echo "<div class='ibram_config_sep'><p class='ibram_divider'>.</p>";
            esc_attr_e("And here, collections  \"Descarte\" and \"Desaparecimento\"", $this->plugin_name);
            echo "</div>";
        } else if (4 === $index) {
            echo "<br><br>";
        }
    }
}