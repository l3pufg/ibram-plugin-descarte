<?php
/**
 * Classe responsavel por iniciar a execuacao da importacao do set
 * User: desenvolvedor
 * Date: 24/08/2017
 * Time: 16:41
 */
include_once dirname(__FILE__).'/class-helper-file-import-data-set.php';
include_once dirname(__FILE__).'/class-mapping-import-data-set.php';
include_once dirname(__FILE__).'/class-persist-methods-import-data-set.php';

 class ImportDataSet{

    public static function start(){
        error_reporting(E_ALL);
        error_log('Error creating the CE Labels Plugin db tables!', 0);
        // busco os dados do repositorio metadados/informacoes gerais
        $repository = HelperFileImportDataSet::getRepository();
        // busco os dados das colecoes  metadados/informacoes gerais
        $collections = HelperFileImportDataSet::getCollections();
        // inicio o mapeamento ou busco o ja existente
        MappingImportDataSet::initMap();

        // adicionando ou atualizando os metadados do repositorio
        self::proccessRepositoryProperties($repository);

        // adicionando ou atualizando os metadados da colecao e suas configuracoes gerais
        self::proccessCollections($collections);

        // atualizo as informacoes gerais do repositorio, desmembrado pois depende das colecoes
        PersistMethodsImportDataSet::updateRepository($repository);

        //atualizo as referencias de metadados para valores antigos
        self::updateReferences();

        //removo possiveis colecoes,metadados ou categorias desatualizadas
        self::garbageCollector();

        //criar o menu
        self::createMenus();

        //salvo o mapeamento
        MappingImportDataSet::saveMap();

        die;
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
             PersistMethodsImportDataSet::manageCollection($collection);
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
                             $values[]  =  $map['categories'][$p];
                         }else{
                             $values[]  =  $p;
                         }
                         PersistMethodsImportDataSet::saveMetaById('term',$meta_id,implode(',',$values));
                     }
                 }else{
                     $old_id = str_replace('_reference','',$old_id);
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
         if ($map) {
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

//         if ($map) {
//             foreach ($map as $index => $array) {
//                 if($index == 'collections'){
//                     foreach ($array as $id_blog) {
//                         wp_delete_post($id_blog,true);
//                     }
//                 }else if($index == 'categories' || $index == 'properties'){
//                     foreach ($array as $id_blog) {
//                         wp_delete_term($id_blog,($index == 'categories') ? 'socialdb_category_type' : 'socialdb_property_type');
//                     }
//                 }
//             }
//         }
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

            //if(!$posts || empty($posts)){
                $bens_id =  self::createPostMenu('Bens',1,$menu_id);
                self::addMetasMenu($bens_id,0,'/#');

                $museologico_id = self::createPostMenu('Museológico',2,$menu_id);
                self::addMetasMenu($museologico_id,$bens_id,'/#');

                // Filhos de museologico
                $permanente_id = self::createPostMenu('Permanente',3,$menu_id);
                self::addMetasMenu($permanente_id,$museologico_id,'/permanente');

                $temporario_id = self::createPostMenu('Temporário',4,$menu_id);
                self::addMetasMenu($temporario_id,$museologico_id,'/bem-temporario');

                $colecoes_id = self::createPostMenu('Coleções',5,$menu_id);
                self::addMetasMenu($colecoes_id,$museologico_id,'/colecoes-2');

                $conjuntos_id = self::createPostMenu('Conjuntos',6,$menu_id);
                self::addMetasMenu($conjuntos_id,$museologico_id,'/conjuntos',$menu_id);

                //raiz
                $desdesa_id =  self::createPostMenu('Descarte/Desaparecimento',7,$menu_id);
                self::addMetasMenu($desdesa_id,0,'/#');

                $descarte_id = self::createPostMenu('Descarte(Baixa)',8,$menu_id);
                self::addMetasMenu($descarte_id,$desdesa_id,'/descarte');

                $desaparecimento_id = self::createPostMenu('Desaparecimento',9,$menu_id);
                self::addMetasMenu($desaparecimento_id,$desdesa_id,'/desaparecimtno');

                //raiz
                $adm_id =  self::createPostMenu('Administração',10,$menu_id);
                self::addMetasMenu($adm_id,0,'/#');

                $ent_id =  self::createPostMenu('Entidades',11,$menu_id);
                self::addMetasMenu($ent_id,$adm_id,'/entidades');

                $loc_id =  self::createPostMenu('Localização',12,$menu_id);
                self::addMetasMenu($loc_id,$adm_id,'/localizacao');

                $matter_id =  self::createPostMenu('Assunto',13,$menu_id);
                self::addMetasMenu($matter_id,$adm_id,'/assunto');

                $par_id =  self::createPostMenu('Parâmetros do sistema',14,$menu_id);
                self::addMetasMenu($par_id,$adm_id,'/instituicao-utilizadora/insituição-utilizadora/editar/');
            //}
     }

     private static function createPostMenu($title,$order,$menu_id){
            $args = [
                'post_title'=> $title,
                'post_type' => 'nav_menu_item',
                'menu_order' => $order
            ];
            $post_id = wp_insert_post($args);
            wp_set_object_terms($post_id,$menu_id,'nav_menu');
            return $post_id;

     }

     private static function addMetasMenu($menu_id,$parent = 0,$url = ''){
         update_post_meta($menu_id,'_menu_item_url',bloginfo('url').$url);
         update_post_meta($menu_id,'_menu_item_xfn',bloginfo('url').$url);
         update_post_meta($menu_id,'_menu_item_classes','a:1:{i:0;s:0:"";}');
         update_post_meta($menu_id,'_menu_item_target','');
         update_post_meta($menu_id,'_menu_item_object','custom');
         update_post_meta($menu_id,'_menu_item_type','custom');
         update_post_meta($menu_id,'_menu_item_object_id',$menu_id);
         if($parent)
             update_post_meta($menu_id,'_menu_item_menu_item_parent',$parent);
     }

}