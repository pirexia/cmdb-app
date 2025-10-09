<?php
// app/Models/Sequence.php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Clase Sequence
 * Gestiona secuencias numéricas en la base de datos de forma segura.
 */
class Sequence
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene el siguiente valor de una secuencia de forma atómica.
     * @param string $sequenceName El nombre de la secuencia.
     * @return int El siguiente valor de la secuencia.
     * @throws PDOException Si la operación falla.
     */
    public function getNextValue(string $sequenceName): int
    {
        try {
            // Esta operación bloquea la fila para evitar race conditions.
            $this->db->exec("UPDATE secuencias SET valor_actual = LAST_INSERT_ID(valor_actual + 1) WHERE nombre_secuencia = " . $this->db->quote($sequenceName));
            $stmt = $this->db->query("SELECT LAST_INSERT_ID()");
            $nextValue = (int) $stmt->fetchColumn();
            return $nextValue;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar para que el servicio lo maneje.
        }
    }
}