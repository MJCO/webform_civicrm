<?php

namespace Drupal\webform_civicrm\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Include legacy files for their procedural functions.
// @todo convert required functions into injectable services.
include_once __DIR__ . '/../../../includes/wf_crm_webform_base.inc';

/**
 * CiviCRM Webform Handler plugin.
 *
 * @WebformHandler(
 *   id = "webform_civicrm",
 *   label = @Translation("CiviCRM"),
 *   category = @Translation("CRM"),
 *   description = @Translation("Create some data in CiviCRM."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class CivicrmWebformHandler extends WebformHandlerBase {

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->civicrm = $container->get('civicrm');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage the CiviCRM settings from the CiviCRM tab'),
      '#url' => new Url('entity.webform.civicrm', ['webform' => $this->getWebform()->id()]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsConditions() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data' => [
        'contact' => [
          1 => [
            'contact' => [
              1 => [
                'contact_type' => 'individual',
                'contact_sub_type' => [],
              ],
            ],
          ],
        ],
        'reg_options' => [
          'validate' => 1,
        ],
      ],
      'confirm_subscription' => 1,
      'create_fieldsets' => 1,
      // The default configuration is invoked before a webform is set to the
      // plugin, so we have to default this to empty.
      'new_contact_source' => '',
      'civicrm_1_contact_1_contact_first_name' => 'create_civicrm_webform_element',
      'civicrm_1_contact_1_contact_last_name' => 'create_civicrm_webform_element',
      'civicrm_1_contact_1_contact_existing' => 'create_civicrm_webform_element',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
    $this->civicrm->initialize();
    $settings = $this->configuration;
    $data = $settings['data'];
    parent::alterElements($elements, $webform); // TODO: Change the autogenerated stub
  }

  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->civicrm->initialize();
    $settings = $this->configuration;
    $data = $settings['data'];
    $processor = \Drupal::service('webform_civicrm.preprocess')->initialize($form, $form_state, $this);
    $processor->alterForm();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['module' => ['webform_civicrm']];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->civicrm->initialize();
    $processor = \Drupal::service('webform_civicrm.postprocess')->initialize($webform_submission->getWebform());
    $processor->validate($form, $form_state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $this->civicrm->initialize();
    $processor = \Drupal::service('webform_civicrm.postprocess')->initialize($webform_submission->getWebform());
    $processor->preSave($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->civicrm->initialize();
    $processor = \Drupal::service('webform_civicrm.postprocess')->initialize($webform_submission->getWebform());
    $processor->postSave($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    $elements = array_filter($this->webform->getElementsDecoded(), function (array $element) {
      return strpos($element['#form_key'], 'civicrm_') !== 0;
    });
    $this->webform->setElements($elements);
    parent::deleteHandler();
  }

}
