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

$helper = new IBRAM_Tainacan_Helper();
// Which will have their functionalities adapted to IBRAM's business rules.
?>

 <div class="wrap">

    <h2> <?php echo esc_html(get_admin_page_title()); ?> - Tainacan </h2> <hr>
    
    <form action="options.php" method="post" name="ibram_config">
		<p>
			<?php esc_attr_e("Select below the collections \"Bem Permanente\", \"Bem Bibliogr&aacute;fico\" and \"Bem Arquiv&iacute;stico\"", $this->plugin_name); ?>
			<br/>
		</p>
        <?php
        settings_fields($this->plugin_name); do_settings_sections($this->plugin_name);
        $cnt = 0;
        foreach($helper->special_collections as $collec):
            $selected_perm = $helper->get_selected_collection($collec); 
			$form_title = str_replace("_", " ", ucfirst($collec));
			?>
            <fieldset class="ibram_options">
                <label for="<?php echo $this->plugin_name?>-config">
                    <?php // printf( __("Configure the collection %s", $this->plugin_name),  $form_title ); ?>
                    <?php echo $form_title ?>
                </label>

                <select name="<?php echo $this->plugin_name;?>[<?php echo $collec; ?>]" id="<?php echo $collec; ?>">
                    <option value=""> <?php esc_attr_e( 'All collections', $this->plugin_name ); ?> </option>
                    <?php $helper->get_selected_opts('socialdb_collection', $selected_perm); ?>
                </select>
            </fieldset>
        <?php

        $helper->set_divider($cnt);
        $cnt++;
        endforeach;
        ?>

        <div class="ibram-btn-container">
            <?php submit_button( __('Save', $this->plugin_name), 'primary','submit', TRUE); ?>
        </div>

    </form>
</div>