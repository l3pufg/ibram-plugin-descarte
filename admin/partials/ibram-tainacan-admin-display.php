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
?>

 <div class="wrap">

    <h2> <?php echo esc_html(get_admin_page_title()); ?> - Tainacan </h2> <hr>

    <form action="options.php" method="post" name="ibram_config">
		<p>
			<?php esc_attr_e("Select below the collections \"Bem Permanente\", \"Descarte\" and \"Desaparecimento\"", $this->plugin_name); ?>,
			<br/>
            <?php esc_attr_e("Which will have their functionalities adapted to IBRAM's business rules.", $this->plugin_name); ?>
		</p>
        <?php
        settings_fields($this->plugin_name); do_settings_sections($this->plugin_name);
        foreach($helper->special_collections as $collec):
            $selected_perm = $helper->get_selected_collection($collec); 
			$form_title = str_replace("_", " ", ucfirst($collec));
			?>
            <fieldset>
                <label for="<?php echo $this->plugin_name?>-config">
                    <?php printf( __("Configure the collection %s", $this->plugin_name),  $form_title ); ?>
                </label> <br>

                <select name="<?php echo $this->plugin_name;?>[<?php echo $collec; ?>]" id="<?php echo $collec; ?>">
                    <option value=""> <?php esc_attr_e( 'All collections', $this->plugin_name ); ?> </option>
                    <?php $helper->get_selected_opts('socialdb_collection', $selected_perm); ?>
                </select>
            </fieldset>
            <br>
        <?php
        endforeach;

        submit_button( __('Save', $this->plugin_name), 'primary','submit', TRUE);
        ?>

    </form>
</div>