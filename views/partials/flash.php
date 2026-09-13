<?php
$ok = flash('ok');
$err = flash('error');
?>
<?php if ($ok): ?>
    <div class="pg-banner pg-banner-ok" role="status"><?= e($ok) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="pg-banner pg-banner-err" role="alert"><?= e($err) ?></div>
<?php endif; ?>
