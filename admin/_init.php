<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/collections.php';
require_once dirname(__DIR__) . '/includes/content_store.php';
require_once dirname(__DIR__) . '/includes/snapshots.php';

header('Cache-Control: no-store');
$admin = require_admin();
