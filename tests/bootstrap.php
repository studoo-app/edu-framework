<?php
/**
 * @file
 * A script containing any set-up steps required for PHPUnit testing.
 */

require __DIR__ . '/../vendor/autoload.php';
// Driver de profilage factice partagé par les tests du profiler de code
require_once __DIR__ . '/Core/Profiler/FakeProfilerDriver.php';
require_once __DIR__ . '/Core/Logs/SessionUserFixture.php';

// Controllers de test des routes déclarées par attributs (#[Route]) : namespaces AttributeRoutes\
// et InvalidAttributeRoutes\ dans tests/Fixtures/
spl_autoload_register(static function (string $class): void {
    foreach (['AttributeRoutes\\', 'InvalidAttributeRoutes\\'] as $namespace) {
        if (str_starts_with($class, $namespace)) {
            $file = __DIR__ . '/Fixtures/' . str_replace('\\', '/', $class) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    }
});

// ConfigCore::getEnv() lit aussi l'environnement du processus (getenv) : on retire
// APP_ENV hérité du shell ou du conteneur de dev (compose.yaml : APP_ENV=dev), sinon
// la barre de debug s'injecte dans les rendus des tests qui neutralisent $_ENV['APP_ENV']
putenv('APP_ENV');

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');