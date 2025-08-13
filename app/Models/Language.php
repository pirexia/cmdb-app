<?php
// app/Models/Language.php

namespace App\Models;

use PDO;
use PDOException;

class Language
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, codigo, nombre FROM idiomas ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener idiomas: " . $e->getMessage());
            return false;
        }
    }

    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, codigo, nombre FROM idiomas WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener idioma por ID: " . $e->getMessage());
            return false;
        }
    }
    
    // NOTA: Métodos create, update, delete NO incluidos aquí para idiomas,
    // ya que su gestión es más compleja (archivos de traducción, etc.).
    // Un administrador podría tener opciones de activar/desactivar en el futuro.
}
