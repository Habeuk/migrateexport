<?php

namespace Drupal\migrateexport\Services;

use Stephane888\Debug\debugLog;
use Drupal\migrateexport\Services\Exception\ExceptionMigrate;

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
    $types = _node_types_build();
    if (!empty($types['names']))
      $this->listNames = $types['names'];
    else {
      throw new ExceptionMigrate("La variable 'names' n'est pas definie");
    }
    if (!empty($types['types']))
      $this->listTypes = $types['types'];
    else {
      throw new ExceptionMigrate("La variable 'types' n'est pas definie");
    }
  }

  static function debug($data, $filename, $auto = false) {
    debugLog::$path = 'siteweb/itietogo/drupal7/web/sites/all/themes/logs';
    debugLog::logger($data, $filename, $auto, 'kint', '');
  }

}