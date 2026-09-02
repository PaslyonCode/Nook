<?php
// Nook instance configuration.

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'nook';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

const APP_SESSION_NAME = 'nook_session';
const APP_SECRET = 'replace-this-with-a-long-random-string';
const MAX_UPLOAD_MB = 2048;
const GALLERY_PAGE_SIZE = 30;
const PREVIEW_MAX_WIDTH = 700;
const PREVIEW_MAX_HEIGHT = 700;
const SPACE_SESSION_SECONDS = 43200;
const SPACE_REMEMBER_DAYS = 30;

// Optional emergency override. Leave empty to manage the path from Settings.
const STORAGE_ROOT_OVERRIDE = '';
