<?php
/**
 * @file
 * A script containing any set-up steps required for PHPUnit testing.
 */

require __DIR__ . '/../vendor/autoload.php';
// Driver de profilage factice partagé par les tests du profiler de code
require_once __DIR__ . '/Core/Profiler/FakeProfilerDriver.php';

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');