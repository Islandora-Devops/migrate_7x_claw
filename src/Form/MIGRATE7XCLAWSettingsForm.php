<?php

/**
 * @file
 * Contains \Drupal\migrate_7x_claw\Form\MIGRATE7XCLAWSettingsForm.
 */

namespace Drupal\migrate_7x_claw\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure migrate_7x_claw module settings.
 */
class MIGRATE7XCLAWSettingsForm extends ConfigFormBase {
  /**
   * {@interidoc}
   */
  public function getFormId() {
    return 'migrate_7x_claw_settings';
  }

  /**
   * {@interitdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_7x_claw.settings',
      'migrate_plus.migration.islandora_audit_file',
      'migrate_plus.migration.islandora_audit_media',
      'migrate_plus.migration.islandora_corporate',
      'migrate_plus.migration.islandora_files',
      'migrate_plus.migration.islandora_geographic',
      'migrate_plus.migration.islandora_media',
      'migrate_plus.migration.islandora_objects',
      'migrate_plus.migration.islandora_person',
      'migrate_plus.migration.islandora_subject',
    ];
  }

  /**
   * {@interitdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_7x_claw.settings');
    $form['migrate_7x_claw_fedoraConfig'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Islandora 7's Fedora Configuration"),
    ];
    $form['migrate_7x_claw_fedoraConfig']['fedora-endpoint-url'] = [
      '#type' => 'url',
      '#title' => $this->t("Fedora base URL"),
      '#default_value' => $config->get('fedora-endpoint-url'),
      '#required' => TRUE,
    ];
    $form['migrate_7x_claw_fedoraConfig']['oldfedoraUsername'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fedora Username'),
      '#default_value' => $config->get('oldfedoraUsername'),
      '#required' => TRUE,
    ];
    $form['migrate_7x_claw_fedoraConfig']['oldfedorapsswd'] = [
      '#type' => 'password',
      '#title' => $this->t("Fedora User's password."),
      '#suffix' => $this->t('Leave blank to use previously save password.'),
      '#default_value' => $config->get('oldfedorapsswd'),
    ];
    $form['migrate_7x_claw_solrConfig'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Islandora 7's Solr Configuration"),
    ];
    $form['migrate_7x_claw_solrConfig']['solr-endpoint-url'] = [
      '#type' => 'url',
      '#title' => $this->t("Solr base URL"),
      '#default_value' => $config->get('solr-endpoint-url'),
      '#required' => TRUE,
    ];
    $form['migrate_7x_claw_solrConfig']['migrate_7x_claw_solr_q'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr query'),
      '#default_value' => $config->get('migrate_7x_claw_solr_q'),
      '#size' => 160,
      '#maxlength' => 1240,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@interitdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $islandora_audit_file_config = $this->config('migrate_plus.migration.islandora_audit_file');
    $islandora_audit_media_config = $this->config('migrate_plus.migration.islandora_audit_media');
    $islandora_corporate_config = $this->config('migrate_plus.migration.islandora_corporate');
    $islandora_files_config = $this->config('migrate_plus.migration.islandora_files');
    $islandora_geographic_config = $this->config('migrate_plus.migration.islandora_geographic');
    $islandora_media_config = $this->config('migrate_plus.migration.islandora_media');
    $islandora_objects_config = $this->config('migrate_plus.migration.islandora_objects');
    $islandora_person_config = $this->config('migrate_plus.migration.islandora_person');
    $islandora_subject_config = $this->config('migrate_plus.migration.islandora_subject');
    $config = $this->config('migrate_7x_claw.settings');

    $config->set('fedora-endpoint-url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_audit_file_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_audit_media_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_audit_media_config->set('source.constants.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_corporate_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_files_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_files_config->set('source.constants.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_geographic_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_media_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_objects_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_person_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));
    $islandora_subject_config->set('source.fedora_base_url', $form_state->getValue('fedora-endpoint-url'));

    $config->set('oldfedoraUsername', $form_state->getValue('oldfedoraUsername'));
    $islandora_audit_file_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_audit_media_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_corporate_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_files_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_geographic_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_media_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_objects_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_person_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_subject_config->set('source.authentication.username', $form_state->getValue('oldfedoraUsername'));
    $islandora_files_config->set('process.uri.settings.authentication.username', $form_state->getValue('oldfedoraUsername'));

    if (!$form_state->getValue('oldfedorapsswd') == '') {
      $config->set('oldfedorapsswd', $form_state->getValue('oldfedorapsswd'));
      $islandora_audit_file_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_audit_media_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_corporate_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_files_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_geographic_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_media_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_objects_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_person_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_subject_config->set('source.authentication.password', $form_state->getValue('oldfedorapsswd'));
      $islandora_files_config->set('process.uri.settings.authentication.password', $form_state->getValue('oldfedorapsswd'));
    }

    $config->set('solr-endpoint-url', $form_state->getValue('solr-endpoint-url'));
    $islandora_audit_file_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_audit_media_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_corporate_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_files_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_geographic_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_media_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_objects_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_person_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));
    $islandora_subject_config->set('source.solr_base_url', $form_state->getValue('solr-endpoint-url'));

    $config->set('migrate_7x_claw_solr_q', $form_state->getValue('migrate_7x_claw_solr_q'));
    $islandora_audit_file_config->set('source.q', $form_state->getValue('migrate_7x_claw_solr_q'));
    $islandora_audit_media_config->set('source.q', $form_state->getValue('migrate_7x_claw_solr_q'));
    $islandora_files_config->set('source.q', $form_state->getValue('migrate_7x_claw_solr_q'));
    $islandora_media_config->set('source.q', $form_state->getValue('migrate_7x_claw_solr_q'));
    $islandora_objects_config->set('source.q', $form_state->getValue('migrate_7x_claw_solr_q'));

    $config->save();
    $islandora_audit_file_config->save();
    $islandora_audit_media_config->save();
    $islandora_corporate_config->save();
    $islandora_files_config->save();
    $islandora_geographic_config->save();
    $islandora_media_config->save();
    $islandora_objects_config->save();
    $islandora_person_config->save();
    $islandora_subject_config->save();
    parent::submitForm($form, $form_state);
  }

}
