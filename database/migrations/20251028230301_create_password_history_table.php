<?php
// database/migrations/YYYYMMDDHHMMSS_create_password_history_table.php
use Phinx\Migration\AbstractMigration;

class CreatePasswordHistoryTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('password_history');
        $table->addColumn('id_usuario', 'integer')
              ->addColumn('password_hash', 'string', ['limit' => 255])
              ->addColumn('fecha_creacion', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('id_usuario', 'usuarios', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['id_usuario'])
              ->create();
    }
}
