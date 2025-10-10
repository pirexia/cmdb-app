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

Ejecuta las migraciones SQL para crear las tablas necesarias (las encontrarás en el directorio database/).

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
