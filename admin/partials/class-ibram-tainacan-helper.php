<?php
/**
 * User: Rodrigo
 * Date: 28/03/2017
 * Time: 10:31
 */
class IBRAM_Tainacan_Helper extends Ibram_Tainacan {

    public $special_collections = [
        'bem_permanente',
        'descarte',
        'desaparecimento'
    ];

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
}