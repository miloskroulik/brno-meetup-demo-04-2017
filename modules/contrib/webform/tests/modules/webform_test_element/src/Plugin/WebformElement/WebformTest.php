<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_test' element.
 *
 * @WebformElement(
 *   id = "webform_test",
 *   label = @Translation("Test element"),
 *   description = @Translation("Provides a form element for testing.")
 * )
 */
class WebformTest extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
    $element['#element_validate'][] = [get_class($this), 'validate'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array $element, $value, array $options = []) {
    $this->displayMessage(__FUNCTION__);
    return '<i>' . $this->formatText($element, $value, $options) . '</i>';
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, $value, array $options = []) {
    $this->displayMessage(__FUNCTION__);
    return strtoupper($value);
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array $values) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->displayMessage(__FUNCTION__, $update ? 'update' : 'insert');
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function displayMessage($method_name, $context1 = NULL) {
    if (PHP_SAPI != 'cli') {
      $t_args = ['@class_name' => get_class($this), '@method_name' => $method_name, '@context1' => $context1];
      drupal_set_message($this->t('Invoked: @class_name:@method_name @context1', $t_args));
    }
  }

  /**
   * Form API callback. Convert password confirm array to single value.
   */
  public static function validate(array &$element, FormStateInterface $form_state) {
    drupal_set_message(t('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest::validate'));
  }

}
