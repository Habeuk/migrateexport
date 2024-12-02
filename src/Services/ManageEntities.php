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
      dd($entity_get_info[$entity_type_id]);
      $bundles = array_keys($entity_get_info[$entity_type_id]['bundles']);
      return $this->loadResumeEntityType($entity_type_id, $bundles);
    }
    return [];
  }
  
  protected function loadResumeEntityType($entity_type_id = 'node', $bundles = []) {
    $results = [];
    if ($bundles) {
      foreach ($bundles as $bundle => $label) {
        $query = new \EntityFieldQuery();
        $query->entityCondition('entity_type', $entity_type_id, '=')->propertyCondition('type', $bundle, '=');
        $result = $query->count()->execute();
        $results[$bundle] = [
          'label' => $label,
          'count' => $result,
          'fields' => $this->filterField($entity_type_id, $bundle)
        ];
      }
    }
    else {
      $bundle = $entity_type_id;
      $query = new \EntityFieldQuery();
      $query->entityCondition('entity_type', $entity_type_id, '=');
      $result = $query->count()->execute();
      $results[$bundle] = [
        'label' => $label,
        'count' => $result,
        'fields' => $this->filterField($entity_type_id, $bundle)
      ];
    }
    return $results;
  }
  
  /**
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @return array
   */
  protected function filterField($entity_type_id, $bundle) {
    $fields = field_info_instances($entity_type_id, $bundle);
    // self::debug($fields, $entity_type_id . '--' . $bundle);
    $results = [];
    foreach ($fields as $field_name => $field) {
      $results[$field_name] = [
        'label' => $field['label']
      ];
      if (isset($field['widget']))
        $results[$field_name]['widget'] = $field['widget'];
    }
    return $results;
  }
}