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
    if ($bundles) {
      foreach ($bundles as $bundle => $label) {
        $query = new \EntityFieldQuery();
        $query->entityCondition('entity_type', $entity_type_id, '=')->propertyCondition('type', $bundle, '=');
        $result = $query->execute();
        $result = $query->count()->execute();
        $results[$bundle] = [
          'label' => $label,
          'count_entities' => $result, // nom de contenu
          'fields' => $this->filterField($entity_type_id, $bundle),
          'extra_fields' => $this->getBundleExtraFields($entity_type_id, $bundle)
        ];
        if ($entity_base_type)
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
      $fields[$fieldName]['field_type'] = $FieldInfo->getFieldById($field['field_id']);
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