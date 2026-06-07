<?php
// index.php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

start_app_session();

$supportedLangs = ['ru', 'en'];
$lang = (string)($_COOKIE['nook_lang'] ?? 'ru');
if (!in_array($lang, $supportedLangs, true)) {
    $lang = 'ru';
}

$L = [
    'ru' => [
        'login_title' => 'Вход',
        'login_intro' => 'Введите логин и пароль для доступа к Nook.',
        'login_error' => 'Неверный логин или пароль.',
        'login_exception' => 'Ошибка входа: ',
        'username' => 'Логин',
        'password' => 'Пароль',
        'sign_in' => 'Войти',
        'default_credentials' => 'По умолчанию:',
        'change_password_hint' => 'После первого входа лучше сменить пароль.',
        'language' => 'Язык',
        'tagline' => 'Фото, видео и заметки в одном личном архиве',
        'logged_as' => 'Вы вошли как',
        'logout' => 'выйти',
        'note_button' => '+ Заметка',
        'note_hint' => 'Текстовая заметка с Editor.js, картинками внутри текста и хэштэгами.',
        'drop_strong' => 'Перетащите фото или видео сюда',
        'drop_span' => 'Можно сразу несколько файлов — это будет одна группа.',
        'choose_files' => 'Выбрать файлы',
        'filters' => 'Фильтры',
        'search_all' => 'Поиск по всем полям',
        'search_placeholder' => 'Заголовок, текст, описание, файл, тэг...',
        'date_from' => 'Дата от',
        'date_to' => 'Дата до',
        'reset_filters' => 'Сбросить фильтры',
        'trash' => 'Корзина',
        'back_entries' => 'Вернуться к записям',
        'empty_trash' => 'Очистить корзину',
        'hashtags' => 'Хэштэги',
        'all' => 'все',
        'no_tags' => 'Пока нет хэштэгов',
        'loading' => 'Загрузка...',
        'new_group' => 'Новая группа',
        'optional_fields' => 'Все текстовые поля можно оставить пустыми.',
        'close' => 'Закрыть',
        'title' => 'Заголовок',
        'group_title_placeholder' => 'Например: Экспедиция, станция 12',
        'description' => 'Описание',
        'description_placeholder' => 'Любые заметки к группе фото/видео',
        'hashtags_placeholder' => '#море #полевые_данные #2026',
        'hidden_label' => 'Невидимая — доступна через поиск и прямое открытие, но не отображается в общем списке',
        'cancel' => 'Отмена',
        'save' => 'Сохранить',
        'group' => 'Группа',
        'add_media' => 'Добавить фото/видео',
        'media_manage_hint' => 'Ниже можно удалить отдельные файлы. Последний файл удалить нельзя — только всю группу.',
        'to_trash' => 'В корзину',
        'restore' => 'Восстановить',
        'edit' => 'Редактировать',
        'save_changes' => 'Сохранить изменения',
        'note' => 'Заметка',
        'new_note' => 'Новая заметка',
        'note_title_placeholder' => 'Заголовок заметки',
        'note_hashtags_placeholder' => '#идея #экспедиция',
        'note_editor_aria' => 'Основной текст заметки',
        'autosave_hint' => 'Автосохранение каждые 5 минут для уже созданной заметки.',
        'viewer' => 'Просмотр',
    ],
    'en' => [
        'login_title' => 'Sign in',
        'login_intro' => 'Enter your username and password to access Nook.',
        'login_error' => 'Invalid username or password.',
        'login_exception' => 'Sign-in error: ',
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
        'default_credentials' => 'Default:',
        'change_password_hint' => 'Change the password after the first login.',
        'language' => 'Language',
        'tagline' => 'Photos, videos, and notes in one personal archive',
        'logged_as' => 'Signed in as',
        'logout' => 'sign out',
        'note_button' => '+ Note',
        'note_hint' => 'Text note with Editor.js, inline images, and hashtags.',
        'drop_strong' => 'Drop photos or videos here',
        'drop_span' => 'Select several files at once to create one group.',
        'choose_files' => 'Choose files',
        'filters' => 'Filters',
        'search_all' => 'Search all fields',
        'search_placeholder' => 'Title, text, description, file, tag...',
        'date_from' => 'Date from',
        'date_to' => 'Date to',
        'reset_filters' => 'Reset filters',
        'trash' => 'Trash',
        'back_entries' => 'Back to entries',
        'empty_trash' => 'Empty trash',
        'hashtags' => 'Hashtags',
        'all' => 'all',
        'no_tags' => 'No hashtags yet',
        'loading' => 'Loading...',
        'new_group' => 'New group',
        'optional_fields' => 'All text fields are optional.',
        'close' => 'Close',
        'title' => 'Title',
        'group_title_placeholder' => 'For example: Expedition, station 12',
        'description' => 'Description',
        'description_placeholder' => 'Any notes for this photo/video group',
        'hashtags_placeholder' => '#sea #field_data #2026',
        'hidden_label' => 'Hidden — searchable and directly accessible, but not shown in the default feed',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'group' => 'Group',
        'add_media' => 'Add photo/video',
        'media_manage_hint' => 'You can delete individual files below. The last file cannot be deleted — delete the whole group instead.',
        'to_trash' => 'Move to trash',
        'restore' => 'Restore',
        'edit' => 'Edit',
        'save_changes' => 'Save changes',
        'note' => 'Note',
        'new_note' => 'New note',
        'note_title_placeholder' => 'Note title',
        'note_hashtags_placeholder' => '#idea #expedition',
        'note_editor_aria' => 'Note body',
        'autosave_hint' => 'Autosave every 5 minutes for existing notes.',
        'viewer' => 'Viewer',
    ],
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function t(string $key): string
{
    global $L, $lang;
    return $L[$lang][$key] ?? $L['ru'][$key] ?? $key;
}

function lang_selected(string $value): string
{
    global $lang;
    return $lang === $value ? ' selected' : '';
}

if (isset($_GET['logout'])) {
    logout_user();
    header('Location: index.php');
    exit;
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    try {
        if (login_user($username, $password)) {
            header('Location: index.php');
            exit;
        }
        $loginError = t('login_error');
    } catch (Throwable $e) {
        $loginError = t('login_exception') . $e->getMessage();
    }
}

$user = current_user();
$assetVersion = '20260607_nook_i18n_1';
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nook</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/style.css?v=<?= e($assetVersion) ?>">
  <script>
    window.NOOK_LANG = <?= json_encode($lang, JSON_UNESCAPED_SLASHES) ?>;
  </script>
</head>
<body>
<?php if (!$user): ?>
  <main class="login-page">
    <form class="login-card" method="post" action="index.php">
      <input type="hidden" name="action" value="login">
      <img class="login-mark" src="assets/logo.svg" alt="Nook">
      <h1><?= e(t('login_title')) ?></h1>
      <p class="muted"><?= e(t('login_intro')) ?></p>

      <label class="login-field language-field">
        <span><?= e(t('language')) ?></span>
        <select class="input language-select" name="lang">
          <option value="ru"<?= lang_selected('ru') ?>>Русский</option>
          <option value="en"<?= lang_selected('en') ?>>English</option>
        </select>
      </label>

      <?php if ($loginError !== ''): ?>
        <div class="status-box error login-error"><?= e($loginError) ?></div>
      <?php endif; ?>

      <label class="login-field">
        <span><?= e(t('username')) ?></span>
        <input class="input" name="username" type="text" autocomplete="username" required autofocus>
      </label>
      <label class="login-field">
        <span><?= e(t('password')) ?></span>
        <input class="input" name="password" type="password" autocomplete="current-password" required>
      </label>
      <button class="btn btn-primary" type="submit"><?= e(t('sign_in')) ?></button>

      <p class="login-hint"><?= e(t('default_credentials')) ?> <code>admin</code> / <code>admin123</code>. <?= e(t('change_password_hint')) ?></p>
    </form>
  </main>
  <script>
    document.querySelectorAll('.language-select').forEach((select) => {
      select.addEventListener('change', () => {
        const lang = select.value === 'en' ? 'en' : 'ru';
        localStorage.setItem('nook_lang', lang);
        document.cookie = `nook_lang=${lang}; path=/; max-age=31536000; SameSite=Lax`;
        window.location.reload();
      });
    });
  </script>
<?php else: ?>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">
        <img class="brand-mark" src="assets/logo.svg" alt="Nook">
        <div>
          <h1>Nook</h1>
          <p><?= e(t('tagline')) ?></p>
        </div>
      </div>

      <section class="panel language-panel">
        <label class="field-label" for="languageSelect"><?= e(t('language')) ?></label>
        <select id="languageSelect" class="input language-select">
          <option value="ru"<?= lang_selected('ru') ?>>Русский</option>
          <option value="en"<?= lang_selected('en') ?>>English</option>
        </select>
      </section>

      <section class="panel user-panel">
        <div class="user-line">
          <span><?= e(t('logged_as')) ?> <strong><?= e((string)$user['username']) ?></strong></span>
          <a class="link-btn" href="index.php?logout=1"><?= e(t('logout')) ?></a>
        </div>
      </section>

      <section class="panel note-create-panel">
        <button id="newNoteBtn" class="btn btn-note-big" type="button"><?= e(t('note_button')) ?></button>
        <p class="muted panel-note-hint"><?= e(t('note_hint')) ?></p>
      </section>

      <section class="panel upload-panel">
        <div id="dropZone" class="drop-zone" tabindex="0">
          <div class="drop-icon">⇪</div>
          <strong><?= e(t('drop_strong')) ?></strong>
          <span><?= e(t('drop_span')) ?></span>
        </div>
        <button id="chooseFilesBtn" class="btn btn-primary" type="button"><?= e(t('choose_files')) ?></button>
        <input id="fileInput" type="file" accept="image/*,video/*" multiple hidden>
      </section>

      <section class="panel filters-panel">
        <h2><?= e(t('filters')) ?></h2>

        <label class="field-label" for="searchInput"><?= e(t('search_all')) ?></label>
        <input id="searchInput" class="input" type="search" placeholder="<?= e(t('search_placeholder')) ?>">

        <div class="date-grid">
          <div>
            <label class="field-label" for="dateFromInput"><?= e(t('date_from')) ?></label>
            <input id="dateFromInput" class="input" type="date">
          </div>
          <div>
            <label class="field-label" for="dateToInput"><?= e(t('date_to')) ?></label>
            <input id="dateToInput" class="input" type="date">
          </div>
        </div>

        <div class="filter-actions">
          <button id="resetFiltersBtn" class="btn" type="button"><?= e(t('reset_filters')) ?></button>
        </div>
      </section>

      <section class="panel trash-panel">
        <button id="trashModeBtn" class="btn" type="button"><?= e(t('trash')) ?></button>
        <button id="backFromTrashBtn" class="btn" type="button" hidden><?= e(t('back_entries')) ?></button>
        <button id="emptyTrashBtn" class="btn btn-danger" type="button" hidden><?= e(t('empty_trash')) ?></button>
      </section>

      <section class="panel tags-panel">
        <div class="panel-heading">
          <h2><?= e(t('hashtags')) ?></h2>
          <button id="clearTagBtn" class="link-btn" type="button"><?= e(t('all')) ?></button>
        </div>
        <div id="tagsList" class="tags-list muted"><?= e(t('no_tags')) ?></div>
      </section>
    </aside>

    <main class="content">
      <header class="content-header">
        <div>
          <h2 id="contentTitle">Nook</h2>
          <p id="resultInfo" class="muted"><?= e(t('loading')) ?></p>
        </div>
      </header>

      <div id="statusBox" class="status-box" hidden></div>
      <div id="gallery" class="gallery"></div>
    </main>
  </div>

  <div id="uploadModal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal="upload"></div>
    <div class="modal-window modal-wide" role="dialog" aria-modal="true" aria-labelledby="uploadModalTitle">
      <div class="modal-header">
        <div>
          <h2 id="uploadModalTitle"><?= e(t('new_group')) ?></h2>
          <p class="muted"><?= e(t('optional_fields')) ?></p>
        </div>
        <button class="icon-btn" data-close-modal="upload" type="button" aria-label="<?= e(t('close')) ?>">×</button>
      </div>

      <div id="uploadPreview" class="preview-grid"></div>

      <form id="uploadForm" class="card-form">
        <label>
          <span><?= e(t('title')) ?></span>
          <input class="input" name="title" type="text" maxlength="255" placeholder="<?= e(t('group_title_placeholder')) ?>">
        </label>
        <label>
          <span><?= e(t('description')) ?></span>
          <textarea class="input textarea" name="description" rows="4" placeholder="<?= e(t('description_placeholder')) ?>"></textarea>
        </label>
        <label>
          <span><?= e(t('hashtags')) ?></span>
          <input id="uploadHashtagsInput" class="input" name="hashtags" type="text" placeholder="<?= e(t('hashtags_placeholder')) ?>" autocomplete="off" data-hashtag-input>
        </label>
        <label class="checkbox-row">
          <input id="uploadHiddenInput" name="is_hidden" type="checkbox" value="1">
          <span><?= e(t('hidden_label')) ?></span>
        </label>
        <div class="modal-actions">
          <button class="btn" data-close-modal="upload" type="button"><?= e(t('cancel')) ?></button>
          <button id="saveUploadBtn" class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
        </div>
      </form>
    </div>
  </div>

  <div id="cardModal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal="card"></div>
    <div class="modal-window modal-wide" role="dialog" aria-modal="true" aria-labelledby="cardModalTitle">
      <div class="modal-header">
        <div>
          <h2 id="cardModalTitle"><?= e(t('group')) ?></h2>
          <p id="cardModalDate" class="muted"></p>
        </div>
        <button class="icon-btn" data-close-modal="card" type="button" aria-label="<?= e(t('close')) ?>">×</button>
      </div>

      <div id="cardImages" class="viewer-grid"></div>

      <form id="editForm" class="card-form">
        <input name="id" type="hidden">
        <label>
          <span><?= e(t('title')) ?></span>
          <input class="input" name="title" type="text" maxlength="255" readonly>
        </label>
        <label>
          <span><?= e(t('description')) ?></span>
          <textarea class="input textarea" name="description" rows="5" readonly></textarea>
        </label>
        <label>
          <span><?= e(t('hashtags')) ?></span>
          <input id="editHashtagsInput" class="input" name="hashtags" type="text" readonly autocomplete="off" data-hashtag-input>
        </label>
        <label class="checkbox-row">
          <input id="editHiddenInput" name="is_hidden" type="checkbox" value="1" disabled>
          <span><?= e(t('hidden_label')) ?></span>
        </label>

        <div id="imageEditTools" class="image-edit-tools edit-only" hidden>
          <div class="image-tools-top">
            <button id="addImagesBtn" class="btn" type="button"><?= e(t('add_media')) ?></button>
            <input id="addImagesInput" type="file" accept="image/*,video/*" multiple class="visually-hidden">
            <span class="muted"><?= e(t('media_manage_hint')) ?></span>
          </div>
          <div id="imageManageList" class="image-manage-list"></div>
        </div>

        <div id="cardFileList" class="file-list muted" hidden></div>
        <div class="modal-actions split-actions">
          <button id="deleteCardBtn" class="btn btn-danger" type="button"><?= e(t('to_trash')) ?></button>
          <button id="restoreCardBtn" class="btn" type="button" hidden><?= e(t('restore')) ?></button>
          <div class="right-actions">
            <button id="cancelEditBtn" class="btn" type="button" hidden><?= e(t('cancel')) ?></button>
            <button id="editCardBtn" class="btn" type="button"><?= e(t('edit')) ?></button>
            <button id="saveEditBtn" class="btn btn-primary" type="submit" hidden><?= e(t('save_changes')) ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="noteModal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal="note"></div>
    <div class="modal-window modal-note-window" role="dialog" aria-modal="true" aria-labelledby="noteModalTitle">
      <div class="modal-header">
        <div>
          <h2 id="noteModalTitle"><?= e(t('note')) ?></h2>
          <p id="noteModalMeta" class="muted"><?= e(t('new_note')) ?></p>
        </div>
        <button class="icon-btn" data-close-modal="note" type="button" aria-label="<?= e(t('close')) ?>">×</button>
      </div>

      <form id="noteForm" class="card-form note-form">
        <input name="id" type="hidden">
        <label>
          <span><?= e(t('title')) ?></span>
          <input id="noteTitleInput" class="input" name="title" type="text" maxlength="255" placeholder="<?= e(t('note_title_placeholder')) ?>">
        </label>
        <label>
          <span><?= e(t('hashtags')) ?></span>
          <input id="noteHashtagsInput" class="input" name="hashtags" type="text" placeholder="<?= e(t('note_hashtags_placeholder')) ?>" autocomplete="off" data-hashtag-input>
        </label>
        <label class="checkbox-row">
          <input id="noteHiddenInput" name="is_hidden" type="checkbox" value="1">
          <span><?= e(t('hidden_label')) ?></span>
        </label>

        <div id="noteEditor" class="note-editor-host" aria-label="<?= e(t('note_editor_aria')) ?>"></div>

        <div class="note-save-line">
          <span id="noteAutosaveInfo" class="muted"><?= e(t('autosave_hint')) ?></span>
        </div>

        <div class="modal-actions split-actions">
          <button id="deleteNoteBtn" class="btn btn-danger" type="button"><?= e(t('to_trash')) ?></button>
          <button id="restoreNoteBtn" class="btn" type="button" hidden><?= e(t('restore')) ?></button>
          <div class="right-actions">
            <button class="btn" data-close-modal="note" type="button"><?= e(t('close')) ?></button>
            <button id="saveNoteBtn" class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="mediaModal" class="modal media-modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal="media"></div>
    <div class="modal-window media-modal-window" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle">
      <div class="modal-header">
        <div>
          <h2 id="mediaModalTitle"><?= e(t('viewer')) ?></h2>
          <p id="mediaModalCaption" class="muted"></p>
        </div>
        <button class="icon-btn" data-close-modal="media" type="button" aria-label="<?= e(t('close')) ?>">×</button>
      </div>
      <div id="mediaViewer" class="media-viewer"></div>
    </div>
  </div>

  <script src="assets/vendor/editorjs/editorjs.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/header.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/list.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/checklist.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/image.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/image-resizable.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/quote.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/delimiter.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/vendor/editorjs/table.umd.js?v=<?= e($assetVersion) ?>"></script>
  <script src="assets/app.js?v=<?= e($assetVersion) ?>"></script>
<?php endif; ?>
</body>
</html>
