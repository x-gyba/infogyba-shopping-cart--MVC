<?php

namespace App\Core;

use PDO;

/**
 * Model base. Fornece acesso à conexão PDO para todos os models.
 */
abstract class Model
{
    protected ?PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}
