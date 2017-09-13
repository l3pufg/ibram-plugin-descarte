<?php
/**
 * Classe responsavel por iniciar a execucao da importacao do set
 * User: desenvolvedor
 * Date: 24/08/2017
 * Time: 16:41
 */

 class ImportDataSet{

    public static function start(){
        session_write_close();
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        //error_reporting(E_ALL);
        // busco os dados do repositorio metadados/informacoes gerais
        $repository = HelperFileImportDataSet::getRepository();
        // busco os dados das colecoes  metadados/informacoes gerais
        $collections = HelperFileImportDataSet::getCollections();
        // inicio o mapeamento ou busco o ja existente
        MappingImportDataSet::initMap();

        $option = unserialize(get_option('mapping-tainacan-import'));
        if($option){
            return true;
        }

        // adicionando ou atualizando os metadados do repositorio
        self::proccessRepositoryProperties($repository);

        // adicionando ou atualizando os metadados da colecao e suas configuracoes gerais
        self::proccessCollections($collections);

        // atualizo as informacoes gerais do repositorio, desmembrado pois depende das colecoes
        echo '=== Atualizando informacoes gerais do repositorio '.PHP_EOL;
        PersistMethodsImportDataSet::updateRepository($repository);

        //atualizo as referencias de metadados para valores antigos
        echo '=== Atualizando as referencias... '.PHP_EOL;
        self::updateReferences();

        //removo possiveis colecoes,metadados ou categorias desatualizadas
        echo '=== em caso de atualizacao... '.PHP_EOL;
        self::garbageCollector();

        //criar o menu
        echo '=== finalizando os menus e... '.PHP_EOL;
        self::createMenus();

        //salvo o mapeamento
        MappingImportDataSet::saveMap();
    }


     public static function proccessRepositoryProperties($repository){
         $cat_id = get_term_by('slug','socialdb_category','socialdb_category_type')->term_id;
         if($repository['metadata']){
             foreach ($repository['metadata'] as $metadata) {
                 if (strpos($metadata['slug'], 'socialdb_property_fixed') !== false) {
                     PersistMethodsImportDataSet::updateFixedProperty($metadata);
                 } else {
                     $has_id = MappingImportDataSet::hasMap('properties', $metadata['id']);
                     if ($has_id) {
                         PersistMethodsImportDataSet::updateProperty($has_id, $metadata, IBRAM_VERSION, true, false);
                     } else {
                         $id = PersistMethodsImportDataSet::createProperty($metadata, IBRAM_VERSION, true);
                         add_term_meta($cat_id, 'socialdb_category_property_id', $id);
                     }
                 }
             }
         }
     }


     /**
      * @param $collections
      */
     public static function proccessCollections($collections){
         foreach ($collections as $collection){
             $partial = microtime(true);
             PersistMethodsImportDataSet::manageCollection($collection);
             $steptime = microtime(true) - $partial;
             $partial = microtime(true);
             echo ("=== Passo finalizado em {$steptime}s".PHP_EOL);
             echo("==========================================================".PHP_EOL);
         }
     }

     /**
      *
      */
     public static function updateReferences(){
         $map = MappingImportDataSet::$map;
         if(isset($map['references-collection']) && is_array($map['references-collection'])){
             foreach ($map['references-collection'] as $meta_id => $old_id) {
                    $old_id = str_replace('_reference','',$old_id);
                    PersistMethodsImportDataSet::saveMetaById('post',$meta_id,$map['collections'][$old_id]);
             }
         }
         if(isset($map['references-properties']) && is_array($map['references-properties'])){
             foreach ($map['references-properties'] as $meta_id => $old_id) {
                 if(strpos($old_id,',')!==false){
                     $explode = explode(',',$old_id);
                     $values = [];
                     foreach ($explode as $p) {
                         if(strpos($p,'_reference')!==false){
                             $p = str_replace('_reference','',$p);
                             $values[]  =  $map['properties'][$p];
                         }else{
                             $values[]  =  $p;
                         }
                         PersistMethodsImportDataSet::saveMetaById('term',$meta_id,implode(',',$values));
                     }
                 }else{
                     $old_id = str_replace('_reference','',$old_id);
                     PersistMethodsImportDataSet::saveMetaById('term',$meta_id,$map['properties'][$old_id]);
                 }
             }
         }
         if(isset($map['references-categories']) && is_array($map['references-categories'])){
             foreach ($map['references-categories'] as $meta_id => $old_id) {
                 if(strpos($old_id,',')!==false){
                     $explode = explode(',',$old_id);
                     $values = [];
                     foreach ($explode as $p) {
                         if(strpos($p,'_reference')!==false){
                             $p = str_replace('_reference','',$p);
                             if(is_numeric($p) && isset($map['categories'][$p]))
                                 $values[]  =  $map['categories'][$p];
                             else
                                 $values[]  =  '';
                         }else{
                             if(is_numeric($p)  && isset($map['categories'][$p] ))
                                $values[]  =  $p;
                             else
                                 $values[]  =  '';
                         }
                         PersistMethodsImportDataSet::saveMetaById('term',$meta_id,implode(',',$values));
                     }
                 }else{
                     $old_id = str_replace('_reference','',$old_id);
                     if(is_numeric($old_id))
                        PersistMethodsImportDataSet::saveMetaById('term',$meta_id,$map['categories'][$old_id]);
                 }
             }
         }
     }

     /**
      *
      */
     public static function garbageCollector() {
         $map = MappingImportDataSet::$map;
         $token = IBRAM_VERSION;
         $nopes = [get_term_by('slug','socialdb_category','socialdb_category_type')->term_id,get_term_by('slug','socialdb_taxonomy','socialdb_category_type')->term_id];
         if ($map && is_array($map)) {
             foreach ($map as $index => $array) {
                 if($index == 'collections'){
                     foreach ($array as $id_blog) {
                         $has_token = get_post_meta($id_blog, 'socialdb_token', true);
                         if(!$has_token || $has_token != $token){
                             $collection = array(
                                 'ID'=> $id_blog,
                                 'post_status' => 'draft'
                             );
                             wp_update_post($collection);
                         }
                     }
                 }else if($index == 'categories' || $index == 'properties'){
                     foreach ($array as $id_blog) {
                         $has_token = get_term_meta($id_blog, 'socialdb_token', true);
                         if(!$has_token || $has_token != $token){
                             $slug = get_term_by('id',$id_blog,'socialdb_property_type')->slug;
                             if(strpos($slug,'socialdb_property_fixed')!==false)
                                 continue;

                             if(!in_array($id_blog,$nopes) )
                                 wp_delete_term($id_blog,($index == 'categories') ? 'socialdb_category_type': 'socialdb_property_type');
                         }
                     }
                 }
             }
         }
     }

     public static function eliminateAllEntities(){
         // inicio o mapeamento ou busco o ja existente
         MappingImportDataSet::initMap();

         $map = MappingImportDataSet::$map;

         if ($map) {
             foreach ($map as $index => $array) {
                 if($index == 'collections' || $index == 'menu'){
                     foreach ($array as $id_blog) {
                         wp_delete_post($id_blog,true);
                     }
                 }else if($index == 'categories' || $index == 'properties'){
                     foreach ($array as $id_blog) {
                         wp_delete_term($id_blog,($index == 'categories') ? 'socialdb_category_type' : 'socialdb_property_type');
                     }
                 }
             }
         }

         $menu = get_term_by('name','Menu-Ibram','nav_menu');
         wp_delete_term($menu->term_id,'nav_menu');
         update_option('theme_mods_tainacan',[ 0 => false, "nav_menu_locations" => false ]);
         delete_option('mapping-tainacan-import');
     }

     public static function createMenus(){
            $posts = get_posts(['post_type'=>'nav_menu_item']);
            $menu = get_term_by('name','Menu-Ibram','nav_menu');
            if(!$menu){
               $new_menu = wp_insert_term('Menu-Ibram','nav_menu');
               $menu_id = $new_menu['term_id'];
               update_option('theme_mods_tainacan',[ 0 => false, "nav_menu_locations" => [ 'menu-ibram'=>$new_menu['term_id'], 'header-menu'=>$new_menu['term_id']  ] ] );
            }else{
                $menu_id = $menu->term_id;
            }

         $array_mapping = [
             'bem_permanente'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['bem_permanente']),
             'bibliografico'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['bibliografico']),
             'arquivistico'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['arquivistico']),
             'descarte'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['descarte']),
             'desaparecimento'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['desaparecimento']),
             'temporario'=>MappingImportDataSet::hasMap('collections',$settings['ibram-config']['temporario'])
         ];
          $collections =   get_option('ibram-tainacan',$array_mapping);

            if(!MappingImportDataSet::hasMap('menu', 1)){
                $bens_id = self::createPostMenu('Bens', 1, $menu_id);
                self::addMetasMenu($bens_id, 0, '/#');
                MappingImportDataSet::addMap('menu', 1, $bens_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 2)){
                $museologico_id = self::createPostMenu('Museológico',2,$menu_id);
                self::addMetasMenu($museologico_id,$bens_id,'/#');
                MappingImportDataSet::addMap('menu', 2, $museologico_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 3)){
                // Filhos de museologico
                $permanente_id = self::createPostMenu('Permanente',3,$menu_id);
                self::addMetasMenu($permanente_id,$museologico_id,get_permalink($collections['bem_permanente']));
                MappingImportDataSet::addMap('menu', 3, $permanente_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 4)){
                $temporario_id = self::createPostMenu('Temporário',4,$menu_id);
                self::addMetasMenu($temporario_id,$museologico_id,get_permalink($collections['temporario']));
                MappingImportDataSet::addMap('menu', 4, $temporario_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 5)){
                $colecoes_id = self::createPostMenu('Coleções',5,$menu_id);
                self::addMetasMenu($colecoes_id,$museologico_id,site_url().'/colecoes');
                MappingImportDataSet::addMap('menu', 5, $colecoes_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 6)){
                $conjuntos_id = self::createPostMenu('Conjuntos',6,$menu_id);
                self::addMetasMenu($conjuntos_id,$museologico_id,site_url().'/colecoes',$menu_id);
                MappingImportDataSet::addMap('menu', 6, $conjuntos_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 7)){
                //raiz
                $desdesa_id =  self::createPostMenu('Descarte/Desaparecimento',7,$menu_id);
                self::addMetasMenu($desdesa_id,0,'/#');
                MappingImportDataSet::addMap('menu', 7, $desdesa_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 8)){
                $descarte_id = self::createPostMenu('Descarte(Baixa)',8,$menu_id);
                self::addMetasMenu($descarte_id,$desdesa_id,get_permalink($collections['descarte']));
                MappingImportDataSet::addMap('menu', 8, $descarte_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 9)){
                $desaparecimento_id = self::createPostMenu('Desaparecimento',9,$menu_id);
                self::addMetasMenu($desaparecimento_id,$desdesa_id,get_permalink($collections['desaparecimento']));
                MappingImportDataSet::addMap('menu', 9, $desaparecimento_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 10)){
                //raiz
                $adm_id =  self::createPostMenu('Administração',10,$menu_id);
                self::addMetasMenu($adm_id,0,'/#');
                MappingImportDataSet::addMap('menu', 10, $adm_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 11)){
                $ent_id =  self::createPostMenu('Entidades',11,$menu_id);
                self::addMetasMenu($ent_id,$adm_id,site_url().'/entidades');
                MappingImportDataSet::addMap('menu', 11, $ent_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 12)){
                $loc_id =  self::createPostMenu('Localização',12,$menu_id);
                self::addMetasMenu($loc_id,$adm_id,site_url().'/localizacao');
                MappingImportDataSet::addMap('menu', 12, $loc_id);
            }
            if(!MappingImportDataSet::hasMap('menu', 13)){
                $matter_id =  self::createPostMenu('Assunto',13,$menu_id);
                self::addMetasMenu($matter_id,$adm_id,site_url().'/assunto');
                MappingImportDataSet::addMap('menu', 13, $matter_id);
            }
             if(!MappingImportDataSet::hasMap('menu', 14)) {
                 $par_id = self::createPostMenu('Instituição Utilizadora', 14, $menu_id);
                 self::addMetasMenu($par_id, $adm_id, site_url().'/instituicao-utilizadora/insituição-utilizadora/editar/');
                 MappingImportDataSet::addMap('menu', 14, $par_id);
             }
     }

     private static function createPostMenu($title,$order,$menu_id){
            $menu = self::get_id_menu($title,OBJECT,'nav_menu_item');
            if(isset($menu->ID)){
                $post_id = $menu->ID;
            }else {
                $args = [
                    'post_title' => $title,
                    'post_type' => 'nav_menu_item',
                    'menu_order' => $order,
                    'post_status' => 'publish'
                ];
                $post_id = wp_insert_post($args);
                wp_set_object_terms($post_id, $menu_id, 'nav_menu');
            }
            return $post_id;

     }

     /**
      *
      * Funcao que insere o relacionamento de um termo com um objeto
      *
      * @param string $post_name O post_name da colecao.
      * @param string (optional) $output O tipo de retono.
      * @return WP_POST O post da colecao.
      */
      static function get_id_menu($post_title, $output = OBJECT, $type = 'socialdb_collection') {
         global $wpdb;
         $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='$type'", trim($post_title)));
         if ($post)
             return get_post($post, $output);

         return null;
     }

     private static function addMetasMenu($menu_id,$parent = 0,$url = ''){
         update_post_meta($menu_id,'_menu_item_url',$url);
         update_post_meta($menu_id,'_menu_item_xfn',$url);
         update_post_meta($menu_id,'_menu_item_classes','a:1:{i:0;s:0:"";}');
         update_post_meta($menu_id,'_menu_item_target','');
         update_post_meta($menu_id,'_menu_item_object','custom');
         update_post_meta($menu_id,'_menu_item_type','custom');
         update_post_meta($menu_id,'_menu_item_object_id',$menu_id);
         if($parent)
             update_post_meta($menu_id,'_menu_item_menu_item_parent',$parent);
     }

}
