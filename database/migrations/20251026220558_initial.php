<?php

use Phinx\Db\Adapter\MysqlAdapter;

class Initial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute("ALTER DATABASE CHARACTER SET 'utf8mb4';");
        $this->execute("ALTER DATABASE COLLATE='utf8mb4_spanish2_ci';");
        $this->table('secuencias', [
                'id' => false,
                'primary_key' => ['nombre_secuencia'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('nombre_secuencia', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('valor_actual', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'nombre_secuencia',
            ])
            ->create();
        $this->table('activos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('numero_serie', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addColumn('id_tipo_activo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'numero_serie',
            ])
            ->addColumn('id_fabricante', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_tipo_activo',
            ])
            ->addColumn('id_modelo', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_fabricante',
            ])
            ->addColumn('id_estado', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_modelo',
            ])
            ->addColumn('id_ubicacion', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_estado',
            ])
            ->addColumn('id_departamento', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_ubicacion',
            ])
            ->addColumn('id_formato_adquisicion', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_departamento',
            ])
            ->addColumn('id_proveedor_adquisicion', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_formato_adquisicion',
            ])
            ->addColumn('fecha_compra', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'id_proveedor_adquisicion',
            ])
            ->addColumn('precio_compra', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 10,
                'scale' => 2,
                'after' => 'fecha_compra',
            ])
            ->addColumn('fecha_fin_garantia', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'precio_compra',
            ])
            ->addColumn('fecha_fin_mantenimiento', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_garantia',
            ])
            ->addColumn('fecha_fin_vida', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_mantenimiento',
            ])
            ->addColumn('fecha_fin_soporte_mainstream', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_vida',
            ])
            ->addColumn('fecha_fin_soporte_extended', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_soporte_mainstream',
            ])
            ->addColumn('fecha_fin_soporte_extendido', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_soporte_extended',
            ])
            ->addColumn('fecha_venta', 'date', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_fin_soporte_extendido',
            ])
            ->addColumn('valor_residual', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 10,
                'scale' => 2,
                'after' => 'fecha_venta',
            ])
            ->addColumn('descripcion', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'valor_residual',
            ])
            ->addColumn('imagen_ruta', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'descripcion',
            ])
            ->addColumn('fecha_creacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'imagen_ruta',
            ])
            ->addColumn('fecha_actualizacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'fecha_creacion',
            ])
            ->addIndex(['numero_serie', 'id_tipo_activo'], [
                'name' => 'uq_numero_serie_tipo_activo',
                'unique' => true,
            ])
            ->addIndex(['id_tipo_activo'], [
                'name' => 'fk_activos_tipo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_fabricante'], [
                'name' => 'fk_activos_fabricante_idx',
                'unique' => false,
            ])
            ->addIndex(['id_modelo'], [
                'name' => 'fk_activos_modelo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_estado'], [
                'name' => 'fk_activos_estado_idx',
                'unique' => false,
            ])
            ->addIndex(['id_ubicacion'], [
                'name' => 'fk_activos_ubicacion_idx',
                'unique' => false,
            ])
            ->addIndex(['id_departamento'], [
                'name' => 'fk_activos_departamento_idx',
                'unique' => false,
            ])
            ->addIndex(['id_formato_adquisicion'], [
                'name' => 'fk_activos_formato_adquisicion_idx',
                'unique' => false,
            ])
            ->addIndex(['id_proveedor_adquisicion'], [
                'name' => 'fk_activos_proveedor_adquisicion_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('archivos_adjuntos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_activo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('nombre_original', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_activo',
            ])
            ->addColumn('ruta_almacenamiento', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre_original',
            ])
            ->addColumn('tipo_mime', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ruta_almacenamiento',
            ])
            ->addColumn('tamano_bytes', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'tipo_mime',
            ])
            ->addColumn('fecha_subida', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'tamano_bytes',
            ])
            ->addColumn('id_usuario_subida', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'fecha_subida',
            ])
            ->addIndex(['id_activo'], [
                'name' => 'fk_adjuntos_activo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_usuario_subida'], [
                'name' => 'fk_adjuntos_usuario_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('campos_personalizados_definicion', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_tipo_activo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('nombre_campo', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_tipo_activo',
            ])
            ->addColumn('tipo_dato', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre_campo',
            ])
            ->addColumn('es_requerido', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'tipo_dato',
            ])
            ->addColumn('opciones_lista', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'es_requerido',
            ])
            ->addColumn('unidad', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'opciones_lista',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'unidad',
            ])
            ->addIndex(['id_tipo_activo', 'nombre_campo'], [
                'name' => 'id_tipo_activo',
                'unique' => true,
            ])
            ->addIndex(['id_tipo_activo'], [
                'name' => 'fk_cpd_tipo_activo_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('campos_personalizados_valores', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_activo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('id_definicion_campo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_activo',
            ])
            ->addColumn('valor', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_definicion_campo',
            ])
            ->addIndex(['id_activo', 'id_definicion_campo'], [
                'name' => 'id_activo',
                'unique' => true,
            ])
            ->addIndex(['id_activo'], [
                'name' => 'fk_cpv_activo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_definicion_campo'], [
                'name' => 'fk_cpv_definicion_campo_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('configuracion', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('clave', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('valor', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'clave',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'valor',
            ])
            ->addIndex(['clave'], [
                'name' => 'clave',
                'unique' => true,
            ])
            ->create();
        $this->table('configuracion_smtp', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('host', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('port', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'host',
            ])
            ->addColumn('auth_required', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'port',
            ])
            ->addColumn('username', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'auth_required',
            ])
            ->addColumn('password', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'username',
            ])
            ->addColumn('encryption', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'password',
            ])
            ->addColumn('from_email', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'encryption',
            ])
            ->addColumn('from_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'from_email',
            ])
            ->addColumn('activo', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'from_name',
            ])
            ->create();
        $this->table('contratos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('numero_contrato', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('id_tipo_contrato', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'numero_contrato',
            ])
            ->addColumn('id_proveedor', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_tipo_contrato',
            ])
            ->addColumn('fecha_inicio', 'date', [
                'null' => false,
                'after' => 'id_proveedor',
            ])
            ->addColumn('fecha_fin', 'date', [
                'null' => false,
                'after' => 'fecha_inicio',
            ])
            ->addColumn('costo_anual', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 10,
                'scale' => 2,
                'after' => 'fecha_fin',
            ])
            ->addColumn('descripcion', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'costo_anual',
            ])
            ->addColumn('fecha_creacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'descripcion',
            ])
            ->addColumn('fecha_actualizacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'fecha_creacion',
            ])
            ->addIndex(['numero_contrato'], [
                'name' => 'numero_contrato',
                'unique' => true,
            ])
            ->addIndex(['id_tipo_contrato'], [
                'name' => 'fk_contratos_tipo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_proveedor'], [
                'name' => 'fk_contratos_proveedor_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('contrato_activo', [
                'id' => false,
                'primary_key' => ['id_contrato', 'id_activo'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id_contrato', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('id_activo', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_contrato',
            ])
            ->addColumn('fecha_asociacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'id_activo',
            ])
            ->addIndex(['id_activo'], [
                'name' => 'fk_ca_activo_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('departamentos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('dispositivos_confianza', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Almacena tokens para dispositivos de confianza MFA',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_usuario', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('token_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Hash SHA-256 del token del dispositivo',
                'after' => 'id_usuario',
            ])
            ->addColumn('user_agent', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'token_hash',
            ])
            ->addColumn('ip_address', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 45,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_agent',
            ])
            ->addColumn('fecha_expiracion', 'datetime', [
                'null' => false,
                'after' => 'ip_address',
            ])
            ->addColumn('fecha_creacion', 'timestamp', [
                'null' => false,
                'default' => 'current_timestamp()',
                'after' => 'fecha_expiracion',
            ])
            ->addIndex(['token_hash'], [
                'name' => 'uq_token_hash',
                'unique' => true,
            ])
            ->addIndex(['id_usuario'], [
                'name' => 'idx_id_usuario',
                'unique' => false,
            ])
            ->create();
        $this->table('estados_activo', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('fabricantes', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('formatos_adquisicion', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('fuentes_usuario', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre_friendly', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('tipo_fuente', 'enum', [
                'null' => false,
                'limit' => 15,
                'values' => ['local', 'ldap', 'activedirectory'],
                'after' => 'nombre_friendly',
            ])
            ->addColumn('host', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'tipo_fuente',
            ])
            ->addColumn('port', 'integer', [
                'null' => true,
                'default' => '389',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'host',
            ])
            ->addColumn('base_dn', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'port',
            ])
            ->addColumn('bind_dn', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'base_dn',
            ])
            ->addColumn('bind_password', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'bind_dn',
            ])
            ->addColumn('user_filter', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'bind_password',
            ])
            ->addColumn('group_filter', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_filter',
            ])
            ->addColumn('use_tls', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'group_filter',
            ])
            ->addColumn('use_ssl', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'use_tls',
            ])
            ->addColumn('ca_cert_path', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'use_ssl',
            ])
            ->addColumn('timeout', 'integer', [
                'null' => true,
                'default' => '5',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'ca_cert_path',
            ])
            ->addColumn('activo', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'timeout',
            ])
            ->addColumn('fecha_creacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'activo',
            ])
            ->addIndex(['nombre_friendly'], [
                'name' => 'nombre_friendly',
                'unique' => true,
            ])
            ->create();
        $this->table('idiomas', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('codigo_iso', 'string', [
                'null' => false,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'codigo_iso',
            ])
            ->addColumn('activo', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'comment' => '0 = Inactivo, 1 = Activo',
                'after' => 'nombre',
            ])
            ->addColumn('nombre_fichero', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Ej: en.php, es.php',
                'after' => 'activo',
            ])
            ->addIndex(['codigo_iso'], [
                'name' => 'codigo',
                'unique' => true,
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('log_activos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_activo', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('id_usuario', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_activo',
            ])
            ->addColumn('tipo_operacion', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_usuario',
            ])
            ->addColumn('campo_modificado', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'tipo_operacion',
            ])
            ->addColumn('valor_anterior', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'campo_modificado',
            ])
            ->addColumn('valor_nuevo', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'valor_anterior',
            ])
            ->addColumn('fecha_hora', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'valor_nuevo',
            ])
            ->addColumn('descripcion_completa', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'fecha_hora',
            ])
            ->addIndex(['id_activo'], [
                'name' => 'fk_log_activo_idx',
                'unique' => false,
            ])
            ->addIndex(['id_usuario'], [
                'name' => 'fk_log_usuario_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('modelos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_fabricante', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_fabricante',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addColumn('imagen_master_ruta', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'descripcion',
            ])
            ->addIndex(['id_fabricante', 'nombre'], [
                'name' => 'id_fabricante',
                'unique' => true,
            ])
            ->addIndex(['id_fabricante'], [
                'name' => 'fk_modelos_fabricante_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('password_reset_tokens', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('id_usuario', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id',
            ])
            ->addColumn('token', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_usuario',
            ])
            ->addColumn('fecha_expiracion', 'datetime', [
                'null' => false,
                'after' => 'token',
            ])
            ->addColumn('usado', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'fecha_expiracion',
            ])
            ->addIndex(['token'], [
                'name' => 'token',
                'unique' => true,
            ])
            ->addIndex(['id_usuario'], [
                'name' => 'fk_prt_usuario_idx',
                'unique' => false,
            ])
            ->create();
        $this->table('proveedores', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('contacto', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addColumn('telefono', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'contacto',
            ])
            ->addColumn('email', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'telefono',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('roles', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('tipos_activos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('tipos_contrato', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('tipos_notificacion', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Define los tipos de notificaciones que un usuario puede suscribir.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('clave', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('nombre_visible', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'clave',
            ])
            ->addColumn('descripcion', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre_visible',
            ])
            ->addIndex(['clave'], [
                'name' => 'clave',
                'unique' => true,
            ])
            ->create();
        $this->table('ubicaciones', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('descripcion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addColumn('direccion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'descripcion',
            ])
            ->addColumn('codigo_postal', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'direccion',
            ])
            ->addColumn('poblacion', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'codigo_postal',
            ])
            ->addColumn('provincia', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'poblacion',
            ])
            ->addColumn('pais', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'provincia',
            ])
            ->addColumn('latitud', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 10,
                'scale' => 8,
                'after' => 'pais',
            ])
            ->addColumn('longitud', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 11,
                'scale' => 8,
                'after' => 'latitud',
            ])
            ->addIndex(['nombre'], [
                'name' => 'nombre',
                'unique' => true,
            ])
            ->create();
        $this->table('usuarios', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => true,
            ])
            ->addColumn('nombre_usuario', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('nombre', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre_usuario',
            ])
            ->addColumn('apellidos', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 150,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nombre',
            ])
            ->addColumn('preferred_language_code', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 5,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'apellidos',
            ])
            ->addColumn('titulo', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'preferred_language_code',
            ])
            ->addColumn('password_hash', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'titulo',
            ])
            ->addColumn('email', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'password_hash',
            ])
            ->addColumn('profile_image_path', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'email',
            ])
            ->addColumn('mfa_enabled', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'profile_image_path',
            ])
            ->addColumn('mfa_secret', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'mfa_enabled',
            ])
            ->addColumn('id_rol', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'mfa_secret',
            ])
            ->addColumn('id_fuente_usuario', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_rol',
            ])
            ->addColumn('fuente_login_nombre', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id_fuente_usuario',
            ])
            ->addColumn('activo', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'fuente_login_nombre',
            ])
            ->addColumn('fecha_creacion', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'activo',
            ])
            ->addColumn('fecha_ultima_sesion', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'fecha_creacion',
            ])
            ->addIndex(['nombre_usuario'], [
                'name' => 'nombre_usuario',
                'unique' => true,
            ])
            ->addIndex(['email'], [
                'name' => 'email',
                'unique' => true,
            ])
            ->addIndex(['id_rol'], [
                'name' => 'fk_usuarios_rol_idx',
                'unique' => false,
            ])
            ->addIndex(['id_fuente_usuario'], [
                'name' => 'fk_usuarios_fuente',
                'unique' => false,
            ])
            ->create();
        $this->table('usuario_notificacion_preferencias', [
                'id' => false,
                'primary_key' => ['id_usuario', 'id_tipo_notificacion'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Preferencias de notificación por usuario.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id_usuario', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('id_tipo_notificacion', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_usuario',
            ])
            ->addColumn('habilitado', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'id_tipo_notificacion',
            ])
            ->addIndex(['id_tipo_notificacion'], [
                'name' => 'id_tipo_notificacion',
                'unique' => false,
            ])
            ->create();
        $this->table('usuario_preferencias', [
                'id' => false,
                'primary_key' => ['id_usuario'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id_usuario', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('id_idioma', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'id_usuario',
            ])
            ->addIndex(['id_idioma'], [
                'name' => 'fk_up_idioma_idx',
                'unique' => false,
            ])
            ->create();
    }
}
