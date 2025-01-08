<?php

namespace Drupal\migrateexport\Services;

use Stephane888\Debug\debugLog;
use Drupal\migrateexport\Services\Exception\ExceptionMigrate;

/**
 * Contient les fonctions de base pour la gestion des entites.
 *
 * @author stephane
 *        
 */
class ManageEntities {
  protected $entity_get_info = [];
  
  /**
   * Contient toutes les informations sur la definition des champs.
   *
   * @var array
   */
  protected $infosAboutFiels = [];
  
  /**
   * Charge unfichier par son id.
   *
   * @param string $fid
   * @return array|boolean
   */
  function loadFile($fid) {
    /**
     *
     * @var Ambiguous $file
     */
    $file = file_load($fid);
    if ($file) {
      $file->url = file_create_url($file->uri);
      return $file;
    }
    return false;
  }
  
  function loadEntity(string $entity_type_id, string $bundle, $entity_id) {
    $column = $this->getEntityColumnId($entity_type_id);
    $query = new \EntityFieldQuery();
    if ('multifield' == $entity_type_id) {
      $column = $this->getEntityColumnId("node");
      $query->entityCondition('entity_type', 'node', '=');
      $query->fieldCondition($bundle);
      $query->propertyCondition($column, $entity_id);
      $results = $query->execute();
      $ids = [];
      foreach ($results['node'] as $ent) {
        $ids[] = $ent->{$column};
      }
      $conditions = [];
      $reset = false;
      $entities = entity_load('node', $ids, $conditions, $reset);
      if ($entities) {
        return reset($entities);
      }
      return false;
    }
    $query->entityCondition('entity_type', $entity_type_id, '=');
    $query->propertyCondition($column, $entity_id);
    $rresults = $query->execute(\PDO::FETCH_ASSOC);
    if (!empty($rresults[$entity_type_id]) && $column) {
      $ids = [];
      foreach ($rresults[$entity_type_id] as $ent) {
        $ids[] = $ent->{$column};
      }
      $conditions = [];
      $reset = false;
      $entities = entity_load($entity_type_id, $ids, $conditions, $reset);
      /**
       * On ajoute le terme parent au valeur de taxo.
       */
      if ('taxonomy_term' == $entity_type_id) {
        foreach ($entities as $tid => $entity) {
          $parent_tid = $this->getParentTid($entity->tid);
          $entities[$tid]->parent = [
            'und' => [
              [
                'target_id' => $parent_tid
              ]
            ]
          ];
        }
      }
      if ($entities) {
        return reset($entities);
      }
    }
    return false;
  }
  
  /**
   * il faudra tester cette fonction et voir ce quelle renvoie vs
   * loadFullDefinitionOfentity.
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @return array
   */
  function loadEntities(string $entity_type_id, string $bundle, $start = 0, $length = 50) {
    $column = $this->getEntityColumnId($entity_type_id);
    $query = new \EntityFieldQuery();
    if ('taxonomy_term' == $entity_type_id) {
      $query->entityCondition('entity_type', $entity_type_id, '=');
      $query_select = db_select('taxonomy_vocabulary', "vb");
      $query_select->fields("vb", [
        'machine_name',
        'vid'
      ]);
      $query_select->condition('machine_name', $bundle);
      $result_select = $query_select->execute()->fetchAssoc();
      if (isset($result_select['vid'])) {
        $query->propertyCondition('vid', $result_select['vid'], '=');
      }
    }
    elseif ('multifield' == $entity_type_id) {
      $column = $this->getEntityColumnId("node");
      $query->entityCondition('entity_type', 'node', '=');
      $query->fieldCondition($bundle);
      $query->propertyOrderBy($column, 'ASC');
      $query->range($start, $length);
      $results = $query->execute();
      $ids = [];
      foreach ($results['node'] as $ent) {
        $ids[] = $ent->{$column};
      }
      $conditions = [];
      $reset = false;
      $entities = entity_load('node', $ids, $conditions, $reset);
      return $entities;
    }
    else {
      $query->entityCondition('entity_type', $entity_type_id, '=');
      $query->propertyCondition('type', $bundle, '=');
    }
    //
    $query->propertyOrderBy($column, 'ASC');
    $query->range($start, $length);
    $rresults = $query->execute(\PDO::FETCH_ASSOC);
    
    if (!empty($rresults[$entity_type_id]) && $column) {
      $ids = [];
      foreach ($rresults[$entity_type_id] as $ent) {
        $ids[] = $ent->{$column};
      }
      $conditions = [];
      $reset = false;
      $entities = entity_load($entity_type_id, $ids, $conditions, $reset);
    }
    /**
     * On ajoute le terme parent au valeur de taxo.
     */
    if ('taxonomy_term' == $entity_type_id) {
      foreach ($entities as $tid => $entity) {
        $parent_tid = $this->getParentTid($entity->tid);
        $entities[$tid]->parent = [
          'und' => [
            [
              'target_id' => $parent_tid
            ]
          ]
        ];
      }
    }
    return $entities;
  }
  
  protected function getParentTid($tid) {
    $query = db_select('taxonomy_term_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.parent = t.tid');
    $query->addField('t', 'tid');
    $query->condition('h.tid', $tid);
    $query->addTag('taxonomy_term_access');
    $query->orderBy('t.weight');
    $query->orderBy('t.name');
    $tid = $query->execute()->fetchAssoc();
    if ($tid) {
      return $tid['tid'];
    }
    return 0;
  }
  
  protected function getEntityColumnId($entity_type_id) {
    $entites = $this->LoadAllentities();
    if (!empty($entites[$entity_type_id]['entity keys'])) {
      return $entites[$entity_type_id]['entity keys']['id'];
    }
    return null;
  }
  
  /**
   * Retourne toutes les definitions d'entites.
   *
   * @return array
   */
  public function LoadAllentities() {
    if (!$this->entity_get_info)
      $this->entity_get_info = entity_get_info();
    return $this->entity_get_info;
  }
  
  /**
   * On formatte les données pour envoyer vers Drupal10.
   */
  // public function LoadFormattersDefinitions() {
  // $entities = $this->LoadAllentities();
  // if (!empty($entities['multifield'])) {
  // $entities['multifield']['bundle keys']['bundle'] = 'type';
  // $entities['paragraph'] = $entities['multifield'];
  // unset($entities['multifield']);
  // }
  // return $entities;
  // }
  
  /**
   * Recupere toutes la definition de l'entité.
   *
   * @param string $entity_type_id
   * @return array
   */
  public function loadFullDefinitionOfentity(string $entity_type_id) {
    $entity_get_info = $this->LoadAllentities();
    if (!empty($entity_get_info[$entity_type_id])) {
      $bundles = [];
      foreach ($entity_get_info[$entity_type_id]['bundles'] as $k => $val) {
        $bundles[$k] = $val['label'];
      }
      $bundle_keys = $entity_get_info[$entity_type_id]['bundle keys'];
      // $entity_keys = $entity_get_info[$entity_type_id]['entity keys'];
      $entity_base_type = null;
      $column_bundle_id = null;
      if (!empty($bundle_keys['bundle'])) {
        $entity_base_type = $entity_type_id . '_' . $bundle_keys['bundle'];
        $column_bundle_id = $bundle_keys['bundle'];
      }
      return $this->loadResumeEntityType($entity_type_id, $bundles, $entity_base_type, $column_bundle_id);
    }
    return [];
  }
  
  public function loadFullDefinitionOfentityAndbundle(string $entity_type_id, $bundle) {
    $entity_get_info = $this->LoadAllentities();
    if (!empty($entity_get_info[$entity_type_id])) {
      $bundles = [
        $bundle => $bundle
      ];
      $bundle_keys = $entity_get_info[$entity_type_id]['bundle keys'];
      /**
       * Entité de configuration portant les bundles.
       * example : 'node_type'
       *
       * @var string $entity_base_type
       */
      $entity_base_type = null;
      /**
       * Colonne de la bd portant le bundle
       * example: 'type'
       *
       * @var string $column_bundle_id
       */
      $column_bundle_id = null;
      if (!empty($bundle_keys['bundle'])) {
        $entity_base_type = $entity_type_id . '_' . $bundle_keys['bundle'];
        $column_bundle_id = $bundle_keys['bundle'];
      }
      return $this->loadResumeEntityType($entity_type_id, $bundles, $entity_base_type, $column_bundle_id);
    }
    return [];
  }
  
  /**
   * //
   *
   * @param string $entity_type_id
   * @param array $bundles
   * @param string $entity_base_type
   * @return []
   */
  protected function loadResumeEntityType($entity_type_id = 'node', $bundles = [], $entity_base_type = null, $column_bundle_id = null) {
    $results = [];
    // $MultifieldEntityController = new
    // \MultifieldEntityController($entity_type_id);
    // $entities = entity_load($entity_type_id);
    // // dump($entity_type_id, $entities);
    // dd(\multifield_load_all());
    if ($entity_type_id) {
      foreach ($bundles as $bundle => $label) {
        $query = new \EntityFieldQuery();
        $query->entityCondition('entity_type', $entity_type_id, '=');
        
        if ('taxonomy_term' == $entity_type_id) {
          $query_select = db_select('taxonomy_vocabulary', "vb");
          $query_select->fields("vb", [
            'machine_name',
            'vid'
          ]);
          $query_select->condition('machine_name', $bundle);
          $result_select = $query_select->execute()->fetchAssoc();
          if (isset($result_select['vid'])) {
            $query->propertyCondition('vid', $result_select['vid'], '=');
          }
        }
        elseif ('multifield' == $entity_type_id) {
          $results[$bundle] = [
            'label' => $label,
            'count_entities' => $this->loadDataMultified($bundle, true),
            'fields' => $this->filterField($entity_type_id, $bundle),
            'extra_fields' => []
          ];
          return $results;
        }
        else {
          $query->propertyCondition($column_bundle_id, $bundle, '=');
        }
        
        $result = $query->execute();
        $result = $query->count()->execute();
        $results[$bundle] = [
          'label' => $label,
          'count_entities' => $result, // nom de contenu
          'fields' => $this->filterField($entity_type_id, $bundle),
          'extra_fields' => $this->getBundleExtraFields($entity_type_id, $bundle)
        ];
        if ($entity_base_type && in_array($entity_type_id, [
          'node'
        ]))
          $results[$bundle]['content'] = $this->getEntityTypeData($bundle, $entity_base_type, $column_bundle_id);
      }
    }
    else {
      $bundle = $entity_type_id;
      $query = new \EntityFieldQuery();
      $query->entityCondition('entity_type', $entity_type_id, '=');
      $result = $query->count()->execute();
      $results[$bundle] = [
        'label' => $label,
        'count_entities' => $result,
        'fields' => $this->filterField($entity_type_id, $bundle),
        'extra_fields' => $this->getBundleExtraFields($entity_type_id, $bundle)
      ];
    }
    
    return $results;
  }
  
  protected function loadDataMultified($bundle, $count = true) {
    $query = new \EntityFieldQuery();
    // $query->entityCondition('entity_type', 'node', '=');
    $query->fieldCondition($bundle);
    if ($count) {
      return $query->count()->execute();
    }
  }
  
  protected function getEntityTypeData($bundle, $entity_base_type, $column_bundle_id) {
    $query = db_select($entity_base_type, 'nt')->fields('nt')->condition($column_bundle_id, $bundle);
    $result = $query->execute();
    return $result->fetchAssoc();
  }
  
  protected function getBundleExtraFields($entity_type_id, $bundle) {
    $FieldInfo = _field_info_field_cache();
    return $FieldInfo->getBundleExtraFields($entity_type_id, $bundle);
  }
  
  /**
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @return array
   */
  protected function filterField($entity_type_id, $bundle) {
    $FieldInfo = _field_info_field_cache();
    $fields = $FieldInfo->getBundleInstances($entity_type_id, $bundle);
    foreach ($fields as $fieldName => $field) {
      $fieldType = $FieldInfo->getFieldById($field['field_id']);
      if ($fieldType["type"] == "multifield") {
        $fieldType['sub_fields'] = $this->filterField("multifield", $fieldName);
      }
      $fields[$fieldName]['field_type'] = $fieldType;
    }
    return $fields;
  }
  
  protected function getInfoAboutTypeOfField() {
    if (!$this->infosAboutFiels) {
      $this->infosAboutFiels = _field_info_collate_types();
    }
    $fieldsType = $this->infosAboutFiels['field types'];
    $fieldsWidgetType = $this->infosAboutFiels['widget types'];
    $fieldsFormaterType = $this->infosAboutFiels['formatter types'];
  }
}