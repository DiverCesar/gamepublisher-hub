<?php
declare(strict_types=1);
?>
</section>
</main>

<footer class="app-footer">
<p>© <?= date('Y') ?> <?= htmlspecialchars($config['app']['name'] ?? 'GamePublisher Hub') ?> · Academic project</p>
</footer>
</div>

<script src="<?= htmlspecialchars(asset('js/app.js')) ?>"></script>
</body>
</html>
