<?php
// database/migrations/YYYYMMDDHHMMSS_add_password_changed_at_to_users.php
use Phinx\Migration\AbstractMigration;

class AddPasswordChangedAtToUsers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('usuarios');
        $table->addColumn('password_changed_at', 'datetime', [
            'null' => true,
            'default' => null,
            'after' => 'password_hash',
            'comment' => 'Fecha del último cambio de contraseña'
        ])
        ->update();
    }
}
