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
        } catch (\PDOException $e) {
            error_log('[EduFramework Logs] ' . $e->getMessage());
        }
    }
}