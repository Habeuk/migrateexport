<?php

namespace Drupal\migrateexport\Services;

use Stephane888\Debug\debugLog;
use Drupal\migrateexport\Services\Exception\ExceptionMigrate;

/**
 *
 * @author stephane
 *        
 */
class ManageNodes extends ManageEntities {
  
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
   * il faudra tester cette fonction et voir ce quelle renvoie vs
   * loadFullDefinitionOfentity.
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @return array
   */
  function loadEntities(string $entity_type_id, string $bundle) {
    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type_id, '=')->propertyCondition('type', $bundle, '=');
    $rresults = $query->execute(\PDO::FETCH_ASSOC);
    $column = null;
    $entities = [];
    if ($entity_type_id == 'node')
      $column = 'nid';
    if (!empty($rresults[$entity_type_id]) && $column) {
      $ids = [];
      foreach ($rresults[$entity_type_id] as $ent) {
        $ids[] = $ent->{$column};
      }
      $conditions = [];
      $reset = false;
      $entities = entity_load($entity_type_id, $ids, $conditions, $reset);
    }
    // $this->debug($entities, 'loadEntities', true);
    return $entities;
  }
  
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
  
  static function debug($data, $filename, $auto = false, $max_depth = 5) {
    debugLog::$path = DRUPAL_ROOT . '/sites/logs';
    debugLog::$max_depth = 5;
    debugLog::logger($data, $filename, $auto, 'kint', '');
  }
}