<?php

// Autoloader => chargement automatique des classes depuis le dossier vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Gestion des variables d'environnement
use Dotenv\Dotenv;

// Gestion des fichiers environnement .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

echo $_ENV['DB_HOST'];

