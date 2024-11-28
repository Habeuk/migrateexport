<?php

/**
 *
 * @file
 * Contains \Drupal\migrateexport\Plugin\resource\node\article\v1\Articles__1_0.
 */
namespace Drupal\migrateexport\Plugin\resource\node;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles
 *
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "nodes:1.0",
 *   resource = "nodes",
 *   label = "Nodes",
 *   description = "Export the nodes with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "page"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Nodes__1_0 extends ResourceNode implements ResourceInterface {

  function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // var_dump($this->bundles);
  }

  /**
   * Overrides ResourceEntity::publicFields().
   */
  public function publicFields() {
    $public_fields = parent::publicFields();
    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value'
    );
    return $public_fields;
  }

  function getBundles() {
  }

}