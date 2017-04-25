<?php

namespace Drupal\proof_webform_to_node\Plugin\WebformHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Webform submission debug handler.
 *
 * @WebformHandler(
 *   id = "node_convert",
 *   label = @Translation("Node Convert"),
 *   category = @Translation("Development"),
 *   description = @Translation("Convert webform submissions to nodes."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class NodeConvertWebformHandler extends WebformHandlerBase {

  /**
   * The entity field manager
   *
   * @var \Drupal\Core\EntityEntityFieldManagerInterface
   */
  protected $EntityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $entity_type_manager);
    $this->EntityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform.convert_to_node'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'node_type' => '',
      'node_status' => '',
      'title_field' => ''
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $webform = $this->getWebform();
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $types_for_select = [];
    foreach ($types as $type) {
      $types_for_select[$type->id()] = $type->label();
    }

    $webform_elements =  $webform->getElementsDecodedAndFlattened();
    $webform_elements_for_select = [];
    foreach ($webform_elements as $webform_element_name => $webform_element) {
      if (isset($webform_element['#admin_title'])) {
        $webform_elements_for_select[$webform_element_name] = $webform_element['#admin_title'];
      } else {
        $webform_elements_for_select[$webform_element_name] = $webform_element['#title'];
      }
    }

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Node type'),
      '#description' => $this->t('Select node type of the created nodes. Please note that field names in the webform should be the same as in the node type, so the data could be transferred properly.'),
      '#options' => $types_for_select,
      '#required' => TRUE,
      '#default_value' => $this->configuration['node_type'],
    ];

    $form['node_status'] = array(
      '#type' => 'radios',
      '#title' => t('Node status'),
      '#options' => array(0 => $this->t('Unpublished'), 1 => $this->t('Published')),
      '#default_value' => $this->configuration['node_status'],
    );

    $form['title_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Webform Element to use as title'),
      '#description' => $this->t('Select webform element to be used as node title'),
      '#options' => $webform_elements_for_select,
      '#required' => TRUE,
      '#default_value' => $this->configuration['title_field'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * Acts on a saved webform submission before the insert or update hook is invoked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = FALSE) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);
    $node_type = $this->configuration['node_type'];
    $node_status = $this->configuration['node_status'];
    $title_field = $this->configuration['title_field'];

    // Flatten data.
    // Prioritizing elements before the submissions fields.
    $data = $data['data'] + $data;
    unset($data['data']);
    $webform_fields = array_keys($data);

    $node_fields = array_keys($this->EntityFieldManager->getFieldDefinitions('node', $node_type));
    $transferable_fields = array_intersect($webform_fields, $node_fields);
    $excluded_fields = [
      'uuid'
    ];
    $transferable_fields = array_diff($transferable_fields, $excluded_fields);
    $node = Node::create(['type' => $node_type]);
    foreach ($transferable_fields as $field) {
      $node->set($field, $data[$field]);
    }
    $node->setTitle(\Drupal\Component\Utility\Unicode::truncate($data[$title_field], 30, TRUE, TRUE, 5));
    $node->setPublished($node_status);
    $node->enforceIsNew();
    $node->save();
  }

}
