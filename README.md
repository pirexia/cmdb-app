CMDB App
Aplicación de Gestión de la Configuración (CMDB) desarrollada en PHP utilizando el microframework Slim 4. Permite a los administradores gestionar activos, usuarios, contratos y otros elementos de inventario de TI de manera eficiente.

Características Principales
Gestión de Activos: CRUD completo para activos de TI.

Gestión de Maestros: Módulos para administrar fabricantes, modelos, tipos de activo, estados, ubicaciones, etc.

Campos Personalizados: Flexibilidad para añadir campos personalizados a los activos según su tipo.

Gestión de Usuarios: Soporte para autenticación local, OpenLDAP y Active Directory.

Auditoría de Cambios: Registro de todas las operaciones de creación, modificación y eliminación en los activos.

Importación Masiva: Módulo para importar datos desde archivos CSV utilizando plantillas descargables.

Internacionalización: Interfaz de usuario disponible en múltiples idiomas.

Requisitos del Sistema
PHP 8.3 o superior

Servidor web (Apache o Nginx)

Base de datos MariaDB

Composer para la gestión de dependencias

Extensión php-ldap si se utiliza autenticación con LDAP/AD

Instalación y Configuración
Clonar el repositorio:

git clone https://github.com/pirexia/cmdb-app.git
cd cmdb-app

Instalar dependencias de Composer:

composer install --no-dev

Configurar la base de datos:

Crea una base de datos en MariaDB.

Una vez configurado el `.env` (ver siguiente paso), ejecuta el sistema de migraciones para crear la estructura de la base de datos automáticamente:
```bash
php db-manager.php migrate
```

Configura las credenciales de la base de datos en el archivo .env.

Configurar el archivo .env:

Copia el archivo .env.example y renómbralo a .env.

Ajusta las variables de entorno, como la URL de la aplicación, las credenciales de la base de datos y la configuración SMTP.

Configurar el servidor web (Apache):

Apunta la raíz del documento a la carpeta public de la aplicación.

Asegúrate de que las reglas de reescritura de URL (mod_rewrite) estén habilitadas y que el archivo .htaccess funcione correctamente.

Ajustar permisos:

Concede permisos de escritura al servidor web para el directorio `storage/`. El usuario del servidor web varía según el sistema operativo:
- **Debian/Ubuntu:** `www-data`
- **Red Hat/CentOS/Fedora:** `apache`

Ejecuta el comando correspondiente a tu sistema. Por ejemplo, para un sistema basado en Red Hat/CentOS:

```bash
sudo chown -R apache:apache storage/
```

Uso
Una vez instalado y configurado, puedes acceder a la aplicación desde la URL que hayas definido. La primera vez que accedas, el usuario administrador por defecto se creará automáticamente si está habilitado en el archivo .env.

---

## Gestión de la Base de Datos (Migraciones)

Este proyecto utiliza **Phinx** para gestionar los cambios en el esquema de la base de datos de manera controlada y versionada. El script `db-manager.php` en la raíz del proyecto simplifica el uso de Phinx.

### Requisitos Previos

1.  **Fichero `.env`**: Asegúrate de que tu fichero `.env` esté correctamente configurado con las credenciales de la base de datos (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
2.  **Herramientas de MySQL/MariaDB**: Para que los comandos `dump` e `import` funcionen, las herramientas de línea de comandos `mysqldump` y `mysql` deben estar accesibles en el `PATH` de tu sistema.

### Comandos Disponibles

Todos los comandos se ejecutan desde la raíz del proyecto.

#### 1. Aplicar Migraciones (`migrate`)

Aplica todas las migraciones pendientes. Este es el comando principal para actualizar la base de datos en cualquier entorno.

```bash
php db-manager.php migrate
```

#### 2. Crear una Nueva Migración (`create`)

Crea un nuevo fichero de migración en `database/migrations/`. Debes proporcionar un nombre descriptivo en formato `CamelCase`.

```bash
php db-manager.php create NombreDescriptivoDeLaMigracion
```

**Ejemplo:**
```bash
php db-manager.php create AddLastLoginToUsers
```

#### 3. Revertir la Última Migración (`rollback`)

Deshace la última migración que se aplicó. Útil durante el desarrollo.

```bash
php db-manager.php rollback
```

#### 4. Exportar Esquema (`dump:schema`)

Crea una copia de seguridad con **solo la estructura** de la base de datos. El fichero se guarda en `database/dumps/`.

```bash
php db-manager.php dump:schema
```

#### 5. Exportar Base de Datos Completa (`dump:full`)

Crea una copia de seguridad completa con **estructura y datos**. El fichero se guarda en `database/dumps/`.

```bash
php db-manager.php dump:full
```

#### 6. Importar un Fichero SQL (`import`)

Importa un fichero `.sql` a la base de datos. **¡CUIDADO! Esta operación es destructiva y reemplazará los datos existentes.**

```bash
php db-manager.php import ruta/al/fichero.sql
```

### Flujo de Trabajo Recomendado

1.  **Desarrollo**:
    -   Crea una migración con `php db-manager.php create MiCambio`.
    -   Edita el fichero PHP generado con tus sentencias SQL.
    -   Aplica el cambio en tu BBDD local con `php db-manager.php migrate`.
    -   Haz `commit` del nuevo fichero de migración.

2.  **Despliegue / Actualización de otro entorno**:
    -   Haz `git pull` para obtener los últimos cambios.
    -   Ejecuta `composer install` para actualizar dependencias.
    -   Ejecuta `php db-manager.php migrate` para poner la base de datos al día.
