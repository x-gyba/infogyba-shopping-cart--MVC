<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexão única com o banco de dados via PDO.
 * Substitui a antiga função conecta() de assets/php/conecta.php
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): ?PDO
    {
        if (self::$connection === null) {
            if (APP_ENV === 'production' && DB_PASS === '') {
                error_log('AVISO DE SEGURANÇA: DB_PASS está vazio em produção (.env não configurado corretamente).');
            }

            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                // CORREÇÃO (bug de encoding encontrado em teste real): havia um
                // `SET NAMES 'utf8'` aqui que sobrescrevia o utf8mb4 já correto
                // da DSN acima com o "utf8" legado do MySQL — causava mojibake
                // em acentos (ex.: "Básica" virava "BÃ¡sica"). O charset certo
                // já vem pela DSN; não deve ser sobrescrito.
            } catch (PDOException $e) {
                error_log('Erro de conexão com o banco: ' . $e->getMessage());
                self::$connection = null;
            }
        }

        return self::$connection;
    }
}
