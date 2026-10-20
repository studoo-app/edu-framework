<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

 namespace Studoo\EduFramework\Core\Logs;

class LogsInitialiser
{
    public function exeStructure(): void
    {
        try {
            $pdo = LogsService::getConnect();
            $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='serv_logs';");

            if ($result === false || $result->fetch() === false) {
                $pdo->exec("CREATE TABLE serv_logs (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        event_date TEXT DEFAULT (datetime('now')),
                                        event_desc TEXT
                                    )");
            }

            // Table des requêtes SQL exécutées pendant une requête HTTP,
            // rattachées par l'identifiant du log de la requête (request_id)
            $pdo->exec("CREATE TABLE IF NOT EXISTS serv_sql_logs (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        request_id INTEGER NOT NULL,
                                        query TEXT NOT NULL,
                                        duration_ms REAL NOT NULL,
                                        rows_count INTEGER,
                                        event_date TEXT DEFAULT (datetime('now'))
                                    )");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_serv_sql_logs_request_id ON serv_sql_logs (request_id)");

            // Table des profils de code (profiler de la barre de debug), un profil
            // par requête HTTP, rattaché par l'identifiant du log de la requête (request_id)
            $pdo->exec("CREATE TABLE IF NOT EXISTS serv_code_profiles (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        request_id INTEGER NOT NULL UNIQUE,
                                        driver TEXT NOT NULL,
                                        period_ms REAL NOT NULL,
                                        samples INTEGER NOT NULL,
                                        wall_ms REAL NOT NULL,
                                        peak_mb REAL,
                                        functions_count INTEGER NOT NULL,
                                        top_json TEXT NOT NULL,
                                        data BLOB NOT NULL,
                                        event_date TEXT DEFAULT (datetime('now'))
                                    )");
        } catch (\PDOException $e) {
            error_log('[EduFramework Logs] ' . $e->getMessage());
        }
    }
}