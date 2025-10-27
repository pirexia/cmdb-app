<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class InitialDataSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     * Inserta los datos iniciales y esenciales para que la aplicación funcione.
     * Esto incluye idiomas, roles y la fuente de usuario local.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        // --- 1. Insertar Idiomas ---
        $languages = $this->table('languages');
        $languages->insert([
            [
                'iso_code' => 'es',
                'nombre' => 'Español',
                'nombre_fichero' => 'es.php',
                'activo' => 1
            ],
            [
                'iso_code' => 'en',
                'nombre' => 'English',
                'nombre_fichero' => 'en.php',
                'activo' => 1
            ]
        ])->saveData();

        // --- 2. Insertar Roles ---
        $roles = $this->table('roles');
        $roles->insert([
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total a todas las funcionalidades.'
            ],
            [
                'nombre' => 'Modificacion',
                'descripcion' => 'Puede crear, editar y eliminar activos y contratos.'
            ],
            [
                'nombre' => 'Consulta',
                'descripcion' => 'Solo puede ver la información de los activos.'
            ]
        ])->saveData();

        // --- 3. Insertar Fuente de Usuario Local ---
        $sources = $this->table('sources');
        $sources->insert([
            [
                'nombre_friendly' => 'Usuarios Locales',
                'tipo_fuente' => 'local',
                'activo' => 1
            ]
        ])->saveData();
    }
}
