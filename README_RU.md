# Nook

**Nook** — простое автономное веб-приложение на **PHP + MySQL** для личного архива фото, видео и текстовых заметок.

Без Docker, без фреймворков, без сборщиков. Достаточно обычного веб-сервера с PHP и MySQL/MariaDB.

## Возможности

- загрузка фото и видео;
- загрузка нескольких файлов в одну медиа-группу;
- текстовые заметки;
- локально подключенный Editor.js;
- вставка картинок внутрь заметок;
- изменение размера картинок в заметках: `25%`, `50%`, `75%`, `100%`;
- хэштэги;
- поиск по заголовкам, описаниям, текстам заметок, именам файлов и хэштэгам;
- фильтр по датам;
- скрытые записи: доступны через поиск, но не видны в общем списке;
- корзина;
- восстановление записей из корзины;
- очистка корзины с физическим удалением файлов;
- авторизация по логину и паролю;
- переключатель интерфейса: русский / английский;
- локальный SVG-логотип и favicon.

## Требования

Рекомендуемый минимум:

- Linux-сервер или локальная машина;
- Apache или Nginx;
- PHP 8.1+;
- MySQL 5.7+ или MariaDB 10.3+;
- расширения PHP: `pdo_mysql`, `gd`, `mbstring`, `fileinfo`.

Для Debian/Ubuntu с Apache:

```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-gd php-mbstring php-fileinfo unzip
```

## Структура проекта

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

`uploads/originals` хранит оригиналы фото и видео.  
`uploads/thumbs` хранит миниатюры изображений.  
`uploads/note-images` хранит картинки, вставленные в заметки.

Editor.js и инструменты редактора лежат локально в `assets/vendor/editorjs/`, поэтому интернет и CDN для работы редактора не нужны.

Интерфейс поддерживает русский и английский языки. Выбор языка хранится в браузере через cookie/localStorage и не требует изменений в базе данных.

## Установка с нуля

Скопируйте папку проекта на сервер, например:

```bash
sudo cp -r nook /var/www/html/nook
```

Создайте базу и таблицы:

```bash
mysql -u root -p < /var/www/html/nook/install.sql
```

По умолчанию создается база:

```text
nook
```

Данные входа по умолчанию:

```text
login: admin
password: admin123
```

После первого входа пароль лучше сменить вручную через SQL.

## Настройка подключения к MySQL

Откройте `config.php`:

```bash
sudo nano /var/www/html/nook/config.php
```

Проверьте параметры:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'nook';
const DB_USER = 'root';
const DB_PASS = '';
```

Для отдельного MySQL-пользователя можно сделать так:

```sql
CREATE USER 'nook_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON nook.* TO 'nook_user'@'localhost';
FLUSH PRIVILEGES;
```

И указать в `config.php`:

```php
const DB_USER = 'nook_user';
const DB_PASS = 'strong_password_here';
```

## Права на uploads

```bash
sudo mkdir -p /var/www/html/nook/uploads/originals
sudo mkdir -p /var/www/html/nook/uploads/thumbs
sudo mkdir -p /var/www/html/nook/uploads/note-images
sudo chown -R www-data:www-data /var/www/html/nook/uploads
sudo chmod -R 775 /var/www/html/nook/uploads
```

Если веб-сервер работает не от `www-data`, используйте его пользователя.

## Лимиты загрузки

В приложении лимит одного файла задан в `config.php`:

```php
const MAX_UPLOAD_MB = 2048;
```

Но PHP и веб-сервер тоже должны разрешать такие загрузки.

В `php.ini`:

```ini
file_uploads = On
upload_max_filesize = 2048M
post_max_size = 2200M
max_file_uploads = 200
max_input_time = 600
max_execution_time = 600
memory_limit = 1024M
```

После изменения PHP-настроек перезапустите веб-сервер.

Apache:

```bash
sudo systemctl restart apache2
```

Nginx + PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

Для Nginx также может понадобиться:

```nginx
client_max_body_size 2200M;
```

## Apache VirtualHost

Пример отдельного хоста:

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

Включение:

```bash
sudo a2ensite nook.conf
sudo systemctl reload apache2
```

## Смена пароля администратора

Создайте новый хэш:

```bash
php -r "echo password_hash('NEW_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Обновите пароль:

```sql
USE nook;
UPDATE users SET password_hash = 'PASTE_HASH_HERE' WHERE username = 'admin';
```

## Обновление из старых версий

Для чистой установки используйте только `install.sql`.

Для обновления старой рабочей базы в архиве оставлены SQL-скрипты:

```text
upgrade_auth.sql
upgrade_media.sql
upgrade_notes_trash.sql
upgrade_editorjs_real.sql
```

Перед обновлением обязательно сделайте бэкап базы и папки `uploads`.

## Резервное копирование

База:

```bash
mysqldump -u root -p nook > nook_backup.sql
```

Файлы:

```bash
sudo tar -czf nook_uploads_backup.tar.gz /var/www/html/nook/uploads
```

Для восстановления нужны и база, и папка `uploads`.

## Примечания

- Фото и видео хранятся как файлы на сервере.
- Данные записей, заметки и хэштэги хранятся в MySQL.
- Заметки хранятся в двух видах: JSON Editor.js и HTML для поиска/просмотра.
- Удаление сначала отправляет запись в корзину.
- Очистка корзины удаляет данные и связанные файлы окончательно.
