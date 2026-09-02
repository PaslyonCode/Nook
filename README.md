# Nook

Nook is a self-hosted storage application for photos, video, PDF documents, STL models and notes. It uses PHP with MySQL/MariaDB. Physical files live in a server folder selected by the administrator; the database stores cards, metadata, tags and settings.

Russian documentation: [README_RU.md](README_RU.md).

## Included in this build

- Photos, video, PDF, STL and notes.
- Local Editor.js assets and the full tool bundle under `assets/vendor/editorjs` and `assets/editorjs-full-tools.*`; no runtime CDN is required.
- Multiple virtual Nooks sharing one physical storage root.
- Create, rename and delete Nooks from the Nook list, with dedicated edit/delete controls on every row.
- Optional per-Nook passwords and remembered unlock access.
- Privacy-aware reload behavior: an unprotected Nook stays selected, while reloading from a protected Nook opens the configured default Nook without revoking the existing unlock.
- Search, type/date filters and a hashtag sidebar.
- Masonry-style card layout without large row gaps.
- Pinning, quick card actions and bulk operations.
- Moving cards between Nooks.
- File picker, drag-and-drop and Ctrl+V uploads.
- Immediate background save for new media cards and periodic note autosave.
- Photo, video, PDF and STL viewing.
- Previous/next navigation inside an open card using on-screen arrows or Left/Right. Escape closes only the file viewer and leaves the card open.
- Trash, restore and permanent cleanup.
- Export/import with storage integrity checks.
- Explicit publishing of selected entries to a separate minimal public frontend.

Drag-reordering media inside a card is intentionally not included.

## Requirements

- PHP 8.1 or newer;
- MySQL 5.7+/8.x or MariaDB 10.4+;
- PHP extensions: `pdo_mysql`, `gd`, `zip`, `fileinfo`, `mbstring`, `session`;
- Apache/Nginx, or Laragon for a local Windows installation;
- a storage directory outside the web root that PHP can read and write.

For large uploads, also configure `upload_max_filesize`, `post_max_size`, PHP timeouts and the web server request-body limit.

## Clean installation

### 1. Copy the project

Extract the `nook` directory under the web root, for example:

```text
C:\laragon\www\nook
```

or:

```text
/var/www/html/nook
```

### 2. Create the database

```sql
CREATE DATABASE nook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the single clean-install schema:

```bash
mysql -u root -p nook < install.sql
```

`install.sql` is only for a new, empty database. This release does not ship a separate `upgrade.sql`.

### 3. Configure `config.php`

Set the database connection:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'nook';
const DB_USER = 'root';
const DB_PASS = '';
```

Replace `APP_SECRET` with a long random value as well.

### 4. Sign in

Initial credentials:

```text
username: admin
password: admin123
```

Change both after the first sign-in.

### 5. Configure the storage root

Create a dedicated directory such as:

```text
D:/NookStorage
```

or:

```text
/srv/nook-storage
```

Open Nook settings and enter its absolute path. PHP must be allowed to create directories and files there.

## Updating an existing copy

If the installed application is based on the source archive used for this release:

1. Back up the database, storage root and current application files.
2. Copy this release over the existing installation.
3. Do not import `install.sql` into the live database.
4. Hard-refresh the browser with Ctrl+F5.

No extra `php apply...` command is required.

## Project structure

```text
nook/
├─ index.php                 private UI and sign-in page
├─ api.php                   core authenticated API
├─ ux_api.php                additional authenticated UI actions
├─ ux_bootstrap.php          privacy-aware startup selection
├─ public.php                public frontend
├─ public_api.php            public read-only API
├─ public_admin_api.php      publishing administration API
├─ file.php                  private file delivery
├─ public_file.php           published file delivery
├─ bootstrap.php             database, session, access and storage helpers
├─ config.php                instance configuration
├─ install.sql               clean-install database schema
├─ assets/                   JavaScript, CSS, SVG and local Editor.js files
├─ lib/                      media and package helpers
└─ tools/                    migration and storage repair utilities
```

`ux_api.php` is intentionally preserved as a separate endpoint. This release is based on the verified current project and does not restructure working code merely to reduce the number of files.

## Storage layout

Nook creates these directories below the configured storage root:

```text
files/
previews/
note-images/
exports/
imports/
tmp/
```

Keep the storage root outside the public web root. Move both the database and the complete storage root when migrating an instance.

## Editor.js

The local Editor.js files are included in the release:

```text
assets/vendor/editorjs/editorjs.umd.js
assets/vendor/editorjs/header.umd.js
assets/vendor/editorjs/list.umd.js
assets/vendor/editorjs/checklist.umd.js
assets/vendor/editorjs/quote.umd.js
assets/vendor/editorjs/delimiter.umd.js
assets/vendor/editorjs/table.umd.js
assets/vendor/editorjs/image.umd.js
assets/vendor/editorjs/image-resizable.umd.js
assets/editorjs-full-tools.js
assets/editorjs-full-tools.css
```

Do not omit these files when uploading the project to a server or GitHub.

## Public frontend

Only explicitly published cards are exposed. Private files are served by `file.php`; public delivery uses the separate `public_file.php` endpoint, which verifies the card publication state.

## Backups

Before an update or import, save:

- a MySQL/MariaDB dump;
- the entire user storage root;
- `config.php`;
- the current application directory.

Nook export is not a replacement for a server-level configuration and database backup.
