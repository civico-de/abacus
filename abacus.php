<?php

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'abacus.civix.php';
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function abacus_civicrm_config(\CRM_Core_Config $config): void {
  _abacus_civix_civicrm_config($config);
  // MEDIAN and PERCENTILE read their sorted values from GROUP_CONCAT, which MySQL cuts off
  // silently at 1024 bytes by default; max_allowed_packet bounds any result anyway.
  // ckconform-ignore raw-sql -- a session variable has no APIv4, BAO or SQL builder equivalent
  \CRM_Core_DAO::executeQuery(
    'SET SESSION group_concat_max_len = GREATEST(@@SESSION.group_concat_max_len, @@GLOBAL.max_allowed_packet)',
  );
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function abacus_civicrm_install(): void {
  _abacus_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function abacus_civicrm_enable(): void {
  _abacus_civix_civicrm_enable();
}
