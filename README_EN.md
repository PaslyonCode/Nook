# Nook

**Nook** is a small self-hosted **PHP + MySQL** web app for a personal archive of photos, videos, and text notes.

No Docker, no frameworks, no build step. A regular PHP-enabled web server and MySQL/MariaDB are enough.

## Features

- photo and video uploads;
- multiple media files grouped into one entry;
- text notes;
- locally bundled Editor.js;
- image insertion inside notes;
- simple image resizing inside notes: `25%`, `50%`, `75%`, `100%`;
- hashtags;
- search by title, description, note body, filenames, and hashtags;
- date filters;
- hidden entries: searchable, but not shown in the default feed;
- trash bin;
- restore from trash;
- empty trash with physical file deletion;
- username/password authentication;
- interface language switch: Russian / English;
- local SVG logo and favicon.

## Requirements

Recommended minimum:

- Linux server or local machine;
- Apache or Nginx;
- PHP 8.1+;
- MySQL 5.7+ or MariaDB 10.3+;
- PHP extensions: `pdo_mysql`, `gd`, `mbstring`, `fileinfo`.

For Debian/Ubuntu with Apache:

```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-gd php-mbstring php-fileinfo unzip
```

## Project structure

```text
nook/
├── index.php
├── api.php
├── config.php
├── install.sql
├── README.md
├── README_RU.md
├── README_EN.md
├── assets/
│   ├── app.js
│   ├── style.css
│   ├── logo.svg
│   ├── favicon.svg
│   └── vendor/editorjs/
└── uploads/
    ├── originals/
    ├── thumbs/
    └── note-images/
```

`uploads/originals` stores original photos and videos.  
`uploads/thumbs` stores image thumbnails.  
`uploads/note-images` stores images inserted into notes.

Editor.js and its tools are bundled locally in `assets/vendor/editorjs/`, so the editor does not require CDN or internet access.

The interface supports Russian and English. The selected language is stored in the browser via cookie/localStorage and does not require database changes.

## Fresh installation

Copy the project folder to your web server, for example:

```bash
sudo cp -r nook /var/www/html/nook
```

Create the database and tables:

```bash
mysql -u root -p < /var/www/html/nook/install.sql
```

By default, the database name is:

```text
nook
```

Default credentials:

```text
login: admin
password: admin123
```

Change the password after the first login.

## MySQL configuration

Edit `config.php`:

```bash
sudo nano /var/www/html/nook/config.php
```

Check the connection settings:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'nook';
const DB_USER = 'root';
const DB_PASS = '';
```

To create a dedicated MySQL user:

```sql
CREATE USER 'nook_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON nook.* TO 'nook_user'@'localhost';
FLUSH PRIVILEGES;
```

Then update `config.php`:

```php
const DB_USER = 'nook_user';
const DB_PASS = 'strong_password_here';
```

## Upload directory permissions

```bash
sudo mkdir -p /var/www/html/nook/uploads/originals
sudo mkdir -p /var/www/html/nook/uploads/thumbs
sudo mkdir -p /var/www/html/nook/uploads/note-images
sudo chown -R www-data:www-data /var/www/html/nook/uploads
sudo chmod -R 775 /var/www/html/nook/uploads
```

If your web server uses a different user, replace `www-data` accordingly.

## Upload limits

The application-level single-file limit is defined in `config.php`:

```php
const MAX_UPLOAD_MB = 2048;
```

PHP and the web server must also allow large uploads.

In `php.ini`:

```ini
file_uploads = On
upload_max_filesize = 2048M
post_max_size = 2200M
max_file_uploads = 200
max_input_time = 600
max_execution_time = 600
memory_limit = 1024M
```

Restart the web server after changing PHP settings.

Apache:

```bash
sudo systemctl restart apache2
```

Nginx + PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

For Nginx, you may also need:

```nginx
client_max_body_size 2200M;
```

## Apache VirtualHost example

```apache
<VirtualHost *:80>
    ServerName nook.local
    DocumentRoot /var/www/html/nook

    <Directory /var/www/html/nook>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/nook_error.log
    CustomLog ${APACHE_LOG_DIR}/nook_access.log combined
</VirtualHost>
```

Enable it:

```bash
sudo a2ensite nook.conf
sudo systemctl reload apache2
```

## Change admin password

Generate a new hash:

```bash
php -r "echo password_hash('NEW_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Update the user:

```sql
USE nook;
UPDATE users SET password_hash = 'PASTE_HASH_HERE' WHERE username = 'admin';
```

## Upgrading from older versions

For a fresh installation, use only `install.sql`.

Upgrade scripts for older working databases are included:

```text
upgrade_auth.sql
upgrade_media.sql
upgrade_notes_trash.sql
upgrade_editorjs_real.sql
```

Always back up the database and the `uploads` folder before upgrading.

## Backup

Database:

```bash
mysqldump -u root -p nook > nook_backup.sql
```

Files:

```bash
sudo tar -czf nook_uploads_backup.tar.gz /var/www/html/nook/uploads
```

You need both the database and the `uploads` folder for a full restore.

## Notes

- Photos and videos are stored as files on the server.
- Entries, notes, and hashtags are stored in MySQL.
- Notes are stored both as Editor.js JSON and rendered HTML for search/display.
- Delete moves entries to trash first.
- Emptying the trash permanently deletes database records and related files.
