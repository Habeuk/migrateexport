<?php

namespace Drupal\migrateexport\Services;

use Stephane888\Debug\debugLog;
use Drupal\migrateexport\Services\Exception\ExceptionMigrate;
use Kint\Kint;

/**
 *
 * @author stephane
 *
 */
class ManageNodes {

  /**
   * Un tableau contenant les key=> valeurs de types de nodes.
   *
   * @var array
   */
  protected $listNames = [];

  /**
   *
   * @var array
   */
  protected $listTypes = [];

  /**
   * Charge tous les types de nodes.
   */
  function loadAllTypes() {
    $results = [];
    $types = _node_types_build();
    if (!empty($types->names))
      $this->listNames = $types->names;
    else {
      throw new ExceptionMigrate("La variable 'names' n'est pas definie");
    }
    if (!empty($types->types))
      $this->listTypes = $types->types;
    else {
      throw new ExceptionMigrate("La variable 'types' n'est pas definie");
    }
    $results['node'] = $this->loadResumeEntityType('node', $this->listNames);
    $results['user'] = $this->loadResumeEntityType('user');
    // $results['field_info'] = field_info_instances();
    return $results;
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

  static function debug($data, $filename, $auto = false) {
    debugLog::$path = 'siteweb/itietogo/drupal7/web/sites/all/themes/logs';
    debugLog::$max_depth = 10;
    debugLog::logger($data, $filename, $auto, 'kint', '');
  }

}