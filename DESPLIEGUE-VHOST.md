# Guía de despliegue por VirtualHost — Academia JOSEPAN 360

Despliegue en Apache (Debian) apuntando el **DocumentRoot del subdominio a la carpeta `public/`**. Así la URL queda limpia (sin `/public`) y solo se expone `public/`; el resto del proyecto (`config/`, `includes/`, `sdk_omni/`, `schema.sql`, `CLAUDE.md`, etc.) queda fuera del alcance web.

Ejemplo para el subdominio `academy.josepan.app`. Ajusta rutas, dominio y certificados a tu servidor.

---

## 1. Requisitos del servidor

```bash
# PHP 8 y extensiones necesarias
sudo apt install php php-cli php-mysql php-curl php-mbstring libapache2-mod-php

# Módulos de Apache
sudo a2enmod ssl headers rewrite deflate expires
sudo systemctl restart apache2
```

MySQL/MariaDB en marcha y un usuario con permiso para crear la base de datos local de la academia (o crea la BD y el usuario por adelantado).

---

## 2. Subir el proyecto

Sube el proyecto **completo** (no solo el contenido de `public/`). Estructura esperada en el servidor:

```
/var/www/josepan-academia/
├── config/  includes/  sdk_omni/  templates/  data/  lib/  downloads/
├── schema.sql  README.md  CLAUDE.md  manual-tecnico.html
└── public/        ← este será el DocumentRoot
```

> Las páginas referencian `../includes`, `../config`, etc., por eso el proyecto debe conservar su estructura y el DocumentRoot debe ser la subcarpeta `public/`.

---

## 3. Permisos

El usuario de Apache (`www-data` en Debian) debe poder **escribir** en `config/` (genera `config.php` y `installed.lock`) y en `downloads/`.

```bash
sudo chown -R www-data:www-data /var/www/josepan-academia
sudo find /var/www/josepan-academia -type d -exec chmod 755 {} \;
sudo find /var/www/josepan-academia -type f -exec chmod 644 {} \;
# Escritura para el instalador y la biblioteca:
sudo chmod 775 /var/www/josepan-academia/config /var/www/josepan-academia/downloads
```

---

## 4. VirtualHost

Crea `/etc/apache2/sites-available/academy.josepan.app.conf`:

```apache
# Redirección HTTP -> HTTPS
<VirtualHost *:80>
    ServerName academy.josepan.app
    Redirect permanent / https://academy.josepan.app/
</VirtualHost>

# Sitio HTTPS
<VirtualHost *:443>
    ServerName academy.josepan.app
    DocumentRoot /var/www/josepan-academia/public

    <Directory /var/www/josepan-academia/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/academy.josepan.app/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/academy.josepan.app/privkey.pem

    ErrorLog  ${APACHE_LOG_DIR}/academy_error.log
    CustomLog ${APACHE_LOG_DIR}/academy_access.log combined
</VirtualHost>
```

Notas:
- `AllowOverride All` permite que `public/.htaccess` se aplique. Si por rendimiento prefieres `AllowOverride None`, copia el contenido de `public/.htaccess` dentro del bloque `<Directory>`.
- Si usas Let's Encrypt: `sudo certbot --apache -d academy.josepan.app` puede generar/gestionar el bloque SSL por ti.

---

## 5. Activar y recargar

```bash
sudo a2ensite academy.josepan.app
sudo apache2ctl configtest      # debe decir: Syntax OK
sudo systemctl reload apache2
```

---

## 6. Instalación de la aplicación

1. Abre `https://academy.josepan.app/` — al no estar instalada, redirige a **`/install/`**.
2. Completa el asistente: datos de la BD local + URL del OMNI API CORE (`https://omni.josepan.es`) + opciones de sesión/roles.
3. El instalador crea la BD, carga `schema.sql`, ejecuta los seeders (cursos, 4 sesiones, niveles, parámetros), escribe `config/config.php` y crea el candado `installed.lock`.
4. **Elimina o renombra** el directorio del instalador:
   ```bash
   sudo rm -rf /var/www/josepan-academia/public/install
   ```
5. Sube los PDF de la biblioteca a `downloads/` con los nombres de `data/documentos.php`.

---

## 7. Verificación

| Comprobación | Resultado esperado |
|---|---|
| `https://academy.josepan.app/` | Login de la academia (la URL **no** muestra `/public`) |
| `https://academy.josepan.app/schema.sql` | **404 / 403** (no accesible) |
| `https://academy.josepan.app/README.md` y `/CLAUDE.md` | **404 / 403** |
| `https://academy.josepan.app/manual-tecnico.html` | Manual técnico (copia en `public/`) |
| `http://academy.josepan.app/` | Redirige a `https://` |

Si las cuatro primeras dan el resultado esperado, el despliegue está correcto: solo `public/` queda expuesto y todo lo sensible está fuera del webroot.

---

## 8. Notas

- **HTTPS:** con el vhost `:80` de redirección no necesitas el redirect en `.htaccess` (queda comentado). La cabecera HSTS ya viene activada en `public/.htaccess`.
- **Subcarpeta:** si en lugar de un subdominio lo montas en `https://intranet.josepan.app/academia/`, usa `Alias /academia /var/www/josepan-academia/public` con su bloque `<Directory>`. No hay que tocar el código: las URLs son relativas y la base se calcula sola.
- **Producción:** en `config/config.php`, `SESSION_SECURE = true` y `DEV_MODE = false`.
