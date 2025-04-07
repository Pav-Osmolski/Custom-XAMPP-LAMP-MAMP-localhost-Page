<?php
$apachePathValid = file_exists( APACHE_PATH );
$htdocsPathValid = file_exists( HTDOCS_PATH );
?>

<div id="settings-view" style="display: none;">
    <h2>User Settings Configuration</h2>
    <form method="post">
        <label>DB Host: <input type="text" name="DB_HOST" value="<?= DB_HOST ?>"></label>
        <label>DB User: <input type="text" name="DB_USER" value="<?= DB_USER ?>"></label>
        <label>DB Password: <input type="password" name="DB_PASSWORD" value="<?= DB_PASSWORD ?>"></label>

        <label>Apache Path:
            <input type="text" name="APACHE_PATH" value="<?= APACHE_PATH ?>">
            <?= $apachePathValid ? '✅' : '❌' ?>
        </label>

        <label>HTDocs Path:
            <input type="text" name="HTDOCS_PATH" value="<?= HTDOCS_PATH ?>">
            <?= $htdocsPathValid ? '✅' : '❌' ?>
        </label>

        <label>Display System Stats:
            <input type="checkbox" name="displaySystemStats" <?= $displaySystemStats ? 'checked' : '' ?>>
        </label>

        <label>Display Apache Error Log:
            <input type="checkbox" name="displayApacheErrorLog" <?= $displayApacheErrorLog ? 'checked' : '' ?>>
        </label>

        <label>Use AJAX for Stats:
            <input type="checkbox" name="useAjaxForStats" <?= $useAjaxForStats ? 'checked' : '' ?>>
        </label><br>

        <button type="submit">Save Settings</button>
    </form>
</div>
