<section class="pg-card">
    <h1>Estamos confirmando el pago</h1>
    <p>Mollie avisará a POSITRON por webhook. Si el cobro es correcto, tu órbita quedará activa (o pendiente de activación si así lo ha configurado administración).</p>
    <p>Estado actual: <strong><?= e($subscription['status'] ?? 'en proceso') ?></strong></p>
    <a class="pg-btn pg-btn-ion" href="/cuenta">Ver mi cuenta</a>
    <a class="pg-btn pg-btn-ghost" href="/chat">Intentar el chat</a>
</section>
