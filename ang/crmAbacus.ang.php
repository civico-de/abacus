<?php

declare(strict_types = 1);

// Gives the period functions their own column alias in the SearchKit editor.
return [
  'js' => [
    'ang/crmAbacus.module.js',
  ],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmSearchAdmin'],
];
