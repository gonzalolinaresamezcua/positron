<section class="pg-chat" data-exhausted="<?= $usage['exhausted'] ? '1' : '0' ?>">
    <aside class="pg-chat-side">
        <form method="post" action="/chat/nueva">
            <?= csrf_field() ?>
            <button class="pg-btn pg-btn-ion pg-btn-sm" type="submit">Nueva conversación</button>
        </form>
        <ul class="pg-chat-list">
            <?php foreach ($conversations as $c): ?>
                <li>
                    <a class="<?= isset($current['id']) && (int) $current['id'] === (int) $c['id'] ? 'is-on' : '' ?>"
                       href="/chat?c=<?= e((string) $c['id']) ?>"><?= e($c['title']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="pg-chat-quota">
            <span><?= e((string) $usage['tokens']) ?> tokens · <?= e((string) $usage['requests']) ?> peticiones (mes)</span>
            <?php if ($usage['token_allowance']): ?>
            <div class="pg-meter"><i style="width: <?= e((string) min(100, $usage['token_allowance'] > 0 ? ($usage['tokens'] / $usage['token_allowance']) * 100 : 0)) ?>%"></i></div>
            <?php endif; ?>
        </div>
    </aside>
    <div class="pg-chat-main">
        <div class="pg-chat-stream" id="chat-stream">
            <?php foreach ($messages as $m): ?>
                <?php if ($m['role'] === 'system') { continue; } ?>
                <article class="pg-bubble pg-bubble-<?= e($m['role']) ?>">
                    <p><?= nl2br(e($m['content'])) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if ($messages === []): ?>
                <p class="pg-muted">El rayo gamma está listo. Escribe para hablar con gpt-6-astra.</p>
            <?php endif; ?>
        </div>
        <form class="pg-chat-form" id="chat-form" data-conversation="<?= e((string) ($current['id'] ?? '')) ?>">
            <?= csrf_field() ?>
            <textarea class="pg-input" name="message" id="chat-input" rows="2" required maxlength="8000" placeholder="Pregunta a POSITROM…" <?= $usage['exhausted'] ? 'disabled' : '' ?>></textarea>
            <button class="pg-btn pg-btn-ion" type="submit" <?= $usage['exhausted'] ? 'disabled' : '' ?>>Enviar</button>
        </form>
    </div>
</section>
