<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/l3pufg
 * @since      1.0.0
 *
 * @package    Ibram_Tainacan
 * @subpackage Ibram_Tainacan/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

 <div class="wrap">

    <?php
        $ibram_opts = get_option($this->plugin_name);
        $selected_perm = 0;
        if( $ibram_opts && is_array($ibram_opts) ) {
            if(array_key_exists('bem_permanente', $ibram_opts)) {
                $selected_perm = $ibram_opts['bem_permanente'];
            }
        }
    ?>

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <hr>
    <form action="options.php" method="post" name="ibram_config">

        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        ?>

        <fieldset>
            <label for="<?php echo $this->plugin_name?>-config"> <?php esc_attr_e( 'Config collection \'Bem Permanente\'', $this->plugin_name ); ?> </label>
            <select name="<?php echo $this->plugin_name;?>[bem_permanente]" id="bem_permanente">
                <option value=""> <?php esc_attr_e( 'All collections', $this->plugin_name ); ?> </option>
                <?php collections_opts('socialdb_collection', $selected_perm); ?>
            </select>
        </fieldset>

        <?php submit_button( __('Save','tainacan')  , 'primary','submit', TRUE); ?>

    </form>
</div>


<?php
function collections_opts($post_type, $selected = 0) {
    $post_type_object = get_post_type_object($post_type);
    $label = $post_type_object->label;
    $posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));

    foreach ($posts as $post) {
        echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
    }
}