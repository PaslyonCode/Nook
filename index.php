<?php
// Nook main page.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

start_app_session();
if (isset($_GET['logout'])) {
    logout_user();
    header('Location: index.php');
    exit;
}

$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    try {
        if (login_user((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''))) {
            header('Location: index.php');
            exit;
        }
        $loginError = 'Invalid username or password.';
    } catch (Throwable $e) {
        $loginError = $e->getMessage();
    }
}

$user = current_user();
$assetVersion = '20260807_nook_pin_paste_1';
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nook</title>
  <link rel="icon" href="assets/favicon.svg?v=<?= h($assetVersion) ?>">
  <link rel="stylesheet" href="assets/style.css?v=<?= h($assetVersion) ?>">
  <link rel="stylesheet" href="assets/public-admin.css?v=2">
  <link rel="stylesheet" href="assets/editorjs-full-tools.css?v=20260814-full2">

<script>window.NOOK_ASSET_VERSION=<?= json_encode($assetVersion) ?>;</script>
  <script src="assets/editorjs-full-tools.js?v=20260814-full2"></script>

  <script src="ux_bootstrap.php?v=20260901-9"></script>
  <script src="assets/ux-v3-boot.js?v=20260901-9"></script>
<script src="assets/app.js?v=<?= h($assetVersion) ?>"></script>
  <script src="assets/public-admin.js?v=2"></script>

  <!-- NOOK_UX_V3 -->

  <!-- NOOK_UX_V3 -->

  <!-- NOOK_UX_V3 -->

  <!-- NOOK_UX_V3 -->

  <!-- NOOK_UX_V3 -->
  <link rel="stylesheet" href="assets/ux-v3.css?v=20260901-9">
</head>
<body>
<?php if (!$user): ?>
  <main class="login-page">
    <form class="login-card" method="post">
      <input type="hidden" name="action" value="login">
      <div class="login-topline">
        <img src="assets/logo.svg" class="login-logo" alt="Nook">
        <select id="loginLanguage" class="language-select" aria-label="Language"><option value="ru">Русский</option><option value="en">English</option></select>
      </div>
      <h1>Nook</h1>
      <p data-i18n="loginHint">Войдите, чтобы открыть хранилище.</p>
      <?php if ($loginError !== ''): ?><div class="status-box error"><?= h($loginError) ?></div><?php endif; ?>
      <label><span data-i18n="username">Логин</span><input class="input" name="username" autocomplete="username" required autofocus></label>
      <label><span data-i18n="password">Пароль</span><input class="input" type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn btn-primary full" type="submit" data-i18n="signIn">Войти</button>
      <small data-i18n="defaultCredentials">По умолчанию: admin / admin123</small>
    </form>
  </main>
<?php else: ?>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand"><img src="assets/logo.svg" alt="Nook"><div><h1>Nook</h1><p data-i18n="brandSubtitle">Личное хранилище медиа и заметок</p></div></div>
      <section class="panel current-space-panel"><span data-i18n="currentNook">Текущая нычка</span><strong id="currentSpaceName">—</strong></section>
      <section class="panel upload-panel">
        <button id="newNoteBtn" class="btn btn-note full" type="button" data-i18n="noteButton">Заметка</button>
        <label class="check-line"><input id="batchUploadInput" type="checkbox"><span data-i18n="batchMode">Групповое добавление</span></label>
        <div class="batch-options">
          <label><span data-i18n="sharedHashtag">Хэштэг для всех записей</span><input id="batchHashtagInput" class="input" placeholder="#тэг" disabled></label>
          <label class="check-line"><input id="batchHiddenInput" type="checkbox" disabled><span data-i18n="sharedHidden">Невидимые записи</span></label>
        </div>
        <div id="dropZone" class="drop-zone" tabindex="0"><div class="drop-icon">⇪</div><strong data-i18n="dropTitle">Перетащите файлы сюда</strong><span data-i18n="dropHint">Фото, видео, PDF и STL · можно вставить Ctrl+V</span></div>
        <button id="chooseFilesBtn" class="btn btn-primary full" type="button" data-i18n="chooseFiles">Выбрать файлы</button>
        <input id="fileInput" type="file" accept="image/*,video/*,.pdf,.stl,application/pdf,model/stl" multiple hidden>
      </section>
      <section class="panel filters-panel">
        <h2 data-i18n="filters">Фильтры</h2>
        <input id="searchInput" class="input" type="search" data-i18n-placeholder="searchPlaceholder" placeholder="Поиск по всем полям">
        <div class="date-grid"><input id="dateFromInput" class="input" type="date"><input id="dateToInput" class="input" type="date"></div>
        <select id="typeFilter" class="input">
          <option value="all" data-i18n="allTypes">Все типы</option>
          <option value="image" data-i18n="photos">Фото</option>
          <option value="video" data-i18n="videos">Видео</option>
          <option value="pdf" data-i18n="documents">Документы PDF</option>
          <option value="note" data-i18n="notes">Заметки</option>
          <option value="stl">STL</option>
        </select>
        <button id="resetFiltersBtn" class="btn full" type="button" data-i18n="resetFilters">Сбросить фильтры</button>
      </section>
      <section class="panel"><div class="panel-heading"><h2 data-i18n="hashtags">Хэштэги</h2><button id="clearTagBtn" class="link-btn" type="button" data-i18n="all">все</button></div><div id="tagsList" class="tags-list muted">—</div></section>
      <button id="trashBtn" class="btn full" type="button"><span data-i18n="trash">Корзина</span> <span id="trashCount"></span></button>
    </aside>

    <main class="content">
      <header class="content-header sticky-content-header"><div><h2 id="contentTitle" data-i18n="entries">Записи</h2><p id="resultInfo" class="muted"></p></div><div class="header-actions"><button id="emptyTrashBtn" class="btn btn-danger" type="button" hidden data-i18n="emptyTrash">Очистить корзину</button><button id="backFromTrashBtn" class="btn" type="button" hidden data-i18n="backToEntries">Вернуться к записям</button><button id="mainMenuBtn" class="menu-button" type="button" aria-label="Menu">⋮</button></div></header>
      <div id="mainMenu" class="main-menu" hidden>
        <button data-menu-action="spaces" data-i18n="availableNooks">Доступные нычки</button>
        <button data-menu-action="add-space" data-i18n="addNook">Добавить нычку</button>
        <button data-menu-action="settings" data-i18n="settings">Настройки</button>
        <button data-menu-action="export" data-i18n="export">Экспорт</button>
        <button data-menu-action="import" data-i18n="import">Импорт</button>
        <label class="menu-language"><span data-i18n="language">Язык</span><select id="languageSelect"><option value="ru">Русский</option><option value="en">English</option></select></label>
        <a href="index.php?logout=1" data-i18n="logout">Выйти</a>
      </div>
      <div id="statusBox" class="status-box" hidden></div>
      <div id="gallery" class="gallery"></div>
      <div id="loadSentinel" class="load-sentinel"><span data-i18n="loading">Загрузка…</span></div>
    </main>
  </div>

  <div id="uploadModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="upload"></div><div class="modal-window modal-wide"><header class="modal-header"><h2 data-i18n="newGroup">Новая группа</h2><button class="icon-btn" data-close="upload">×</button></header><div id="uploadPreview" class="preview-grid"></div><form id="uploadForm" class="form-grid"><label><span data-i18n="title">Заголовок</span><input class="input" name="title"></label><label><span data-i18n="description">Описание</span><textarea class="input" name="description"></textarea></label><label><span data-i18n="hashtags">Хэштэги</span><input class="input hashtag-input" name="hashtags"></label><label class="check-line"><input type="checkbox" name="is_hidden" value="1"><span data-i18n="hidden">Невидимая</span></label><footer class="modal-actions"><button class="btn" type="button" data-close="upload" data-i18n="close">Закрыть</button><button id="saveUploadBtn" class="btn btn-primary" data-i18n="save">Сохранить</button></footer></form></div></div>

  <div id="cardModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="card"></div><div class="modal-window modal-wide"><header class="modal-header"><div><h2 id="cardTitle">—</h2><p id="cardDate" class="muted"></p><p id="cardAutosaveState" class="muted autosave-state" aria-live="polite"></p></div><div class="modal-header-actions"><button id="cardPinBtn" class="icon-btn pin-btn" type="button" aria-label="Закрепить" title="Закрепить"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></button><button class="icon-btn" data-close="card">×</button></div></header><div id="cardMedia" class="viewer-grid"></div><form id="cardForm" class="form-grid"><input type="hidden" name="id"><label><span data-i18n="title">Заголовок</span><input class="input" name="title" readonly></label><label><span data-i18n="description">Описание</span><textarea class="input" name="description" readonly></textarea></label><label><span data-i18n="hashtags">Хэштэги</span><input class="input hashtag-input" name="hashtags" readonly></label><label class="check-line"><input type="checkbox" name="is_hidden" disabled><span data-i18n="hidden">Невидимая</span></label><div id="cardEditTools" class="edit-tools" hidden><button id="addMediaBtn" class="btn" type="button" data-i18n="addFiles">Добавить файлы</button><input id="addMediaInput" type="file" accept="image/*,video/*,.pdf,.stl" multiple hidden></div><footer class="modal-actions split"><div><button id="deleteCardBtn" class="btn btn-danger" type="button" data-i18n="delete">Удалить</button><button id="moveCardBtn" class="btn" type="button" data-i18n="moveToNook">Переместить в нычку</button></div><div><button id="cancelCardEditBtn" class="btn" type="button" hidden data-i18n="cancel">Отмена</button><button id="editCardBtn" class="btn" type="button" data-i18n="edit">Редактировать</button><button id="saveCardBtn" class="btn btn-primary" hidden data-i18n="save">Сохранить</button></div></footer></form></div></div>

  <div id="noteModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="note"></div><div class="modal-window note-window"><header class="modal-header"><div><h2 data-i18n="note">Заметка</h2><p id="noteAutosaveState" class="muted"></p></div><div class="modal-header-actions"><button id="notePinBtn" class="icon-btn pin-btn" type="button" aria-label="Закрепить" title="Закрепить"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></button><button class="icon-btn" data-close="note">×</button></div></header><form id="noteForm"><input type="hidden" name="id"><div class="note-top-fields"><input class="input note-title" name="title" data-i18n-placeholder="noteTitle" placeholder="Заголовок"><input class="input hashtag-input" name="hashtags" data-i18n-placeholder="hashtags" placeholder="Хэштэги"><label class="check-line"><input name="is_hidden" type="checkbox" value="1"><span data-i18n="hidden">Невидимая</span></label></div><div class="editor-toolbar"><button type="button" data-editor-command="bold"><b>B</b></button><button type="button" data-editor-command="italic"><i>I</i></button><button type="button" data-editor-command="insertUnorderedList">• List</button><button type="button" id="insertNoteImageBtn" data-i18n="insertImage">Вставить картинку</button></div><div id="noteEditor" class="note-editor" contenteditable="true"></div><input id="noteInlineImageInput" type="file" accept="image/*" hidden><section class="attachments-section"><div class="panel-heading"><h3 data-i18n="attachments">Вложения</h3><button id="addAttachmentBtn" class="btn" type="button" data-i18n="addFiles">Добавить файлы</button></div><input id="attachmentInput" type="file" multiple hidden><div id="attachmentList" class="attachment-list"></div></section><footer class="modal-actions split"><div><button id="deleteNoteBtn" class="btn btn-danger" type="button" data-i18n="delete">Удалить</button><button id="moveNoteBtn" class="btn" type="button" data-i18n="moveToNook">Переместить в нычку</button></div><button id="saveNoteBtn" class="btn btn-primary" type="submit" data-i18n="save">Сохранить</button></footer></form></div></div>

  <div id="viewerModal" class="modal viewer-modal" aria-hidden="true"><div class="modal-backdrop" data-close="viewer"></div><div class="modal-window viewer-window"><header class="modal-header"><div><h2 id="viewerTitle">—</h2><p id="viewerMeta" class="muted"></p></div><div><a id="viewerDownload" class="btn" href="#" data-i18n="download">Скачать</a><button class="icon-btn" data-close="viewer">×</button></div></header><div id="viewerBody" class="viewer-body"></div></div></div>

  <div id="settingsModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="settings"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="settings">Настройки</h2><button class="icon-btn" data-close="settings">×</button></header><form id="settingsForm" class="form-grid"><label><span data-i18n="storageFolder">Папка хранения</span><input id="storageRootInput" class="input" name="storage_root" placeholder="D:/NookStorage или /srv/nook-storage" required></label><p class="muted" data-i18n="storageHelp">Используйте абсолютный путь к папке, доступной PHP на запись.</p><button class="btn btn-primary" data-i18n="save">Сохранить</button></form></div></div>

  <div id="spacesModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="spaces"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="availableNooks">Доступные нычки</h2><button class="icon-btn" data-close="spaces">×</button></header><div id="spacesList" class="spaces-list"></div></div></div>
  <div id="spaceFormModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="space-form"></div><div class="modal-window"><header class="modal-header"><h2 id="spaceFormTitle" data-i18n="addNook">Добавить нычку</h2><button class="icon-btn" type="button" data-close="space-form">×</button></header><form id="spaceForm" class="form-grid"><input type="hidden" name="space_id"><label><span data-i18n="name">Имя</span><input class="input" name="name" maxlength="160" required></label><label id="passwordModeLabel" hidden><span data-i18n="passwordAction">Действие с паролем</span><select class="input" name="password_mode"><option value="keep" data-i18n="keepPassword">Не менять</option><option value="set" data-i18n="setPassword">Установить новый</option><option value="remove" data-i18n="removePassword">Убрать пароль</option></select></label><label id="spacePasswordLabel"><span id="spacePasswordText" data-i18n="spacePasswordOptional">Пароль нычки (необязательно)</span><input class="input" type="password" name="password" autocomplete="new-password"></label><small id="spacePasswordHint" class="muted" hidden data-i18n="newPasswordHint">Введите новый пароль для нычки.</small><button class="btn btn-primary" type="submit" data-i18n="save">Сохранить</button></form></div></div>
  <div id="unlockModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="unlock"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="unlockNook">Открыть нычку</h2><button class="icon-btn" data-close="unlock">×</button></header><form id="unlockForm" class="form-grid"><input type="hidden" name="space_id"><label><span data-i18n="password">Пароль</span><input class="input" type="text" inputmode="text" autocomplete="off" name="password" required autofocus></label><label class="check-line"><input type="checkbox" name="remember" value="1" checked><span data-i18n="rememberAccess">Запомнить доступ на 30 дней</span></label><button class="btn btn-primary" data-i18n="unlock">Открыть</button></form></div></div>
  <div id="moveModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="move"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="moveToNook">Переместить в нычку</h2><button class="icon-btn" data-close="move">×</button></header><form id="moveForm" class="form-grid"><input type="hidden" name="id"><select id="moveSpaceSelect" class="input" name="space_id"></select><button class="btn btn-primary" data-i18n="move">Переместить</button></form></div></div>

  <div id="exportModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="export"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="export">Экспорт</h2><button class="icon-btn" data-close="export">×</button></header><p data-i18n="exportHelp">Архив содержит базу и все файлы и сохраняется в подпапке exports.</p><button id="createExportBtn" class="btn btn-primary" data-i18n="createExport">Создать экспорт</button><div id="exportsList" class="package-list"></div></div></div>
  <div id="importModal" class="modal" aria-hidden="true"><div class="modal-backdrop" data-close="import"></div><div class="modal-window"><header class="modal-header"><h2 data-i18n="import">Импорт</h2><button class="icon-btn" data-close="import">×</button></header><p data-i18n="importHelp">Поместите ZIP или распакованную папку в подпапку imports выбранного хранилища.</p><div id="importsList" class="package-list"></div></div></div>

<?php endif; ?>

<?php if ($user): ?>
  <script src="assets/ux-v3.js?v=20260902-1"></script>
<?php endif; ?>
</body>
</html>
