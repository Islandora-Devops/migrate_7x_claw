<?php

/**
 * @file
 * Stuff is going on here.
 */

/**
 * Get settings.
 */
function get_migrate_setting() {
  return /Drupal::service('migrate_7x_claw.migrate_7x_claw')->getMigrate7xSetting();
}

/**
 * Get settings.
 */
function get_migrate_install() {
  /Drupal::configFactory()
    ->getEditable('migrate_7x_claw.settings')
    ->set('fedora-endpoint-url', \Drupal::config('migrate_7x_claw.settings')
    ->set('oldfedoraUsername', \Drupal::config('migrate_7x_claw.settings')
    ->set('oldfedorapsswd', \Drupal::config('migrate_7x_claw.settings')
    ->set('solr-endpoint-url', \Drupal::config('migrate_7x_claw.settings')
    ->set('migrate_7x_claw_solr_q', \Drupal::config('migrate_7x_claw.settings')
    ->save();
}
