<?php
/** @var array $state */
?>
    </main>

    <?php require __DIR__ . '/modals.php'; ?>

  </div>

  <!-- Hydration data from PHP backend -->
  <script>
    window.__INITIAL_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script type="module" src="app.js"></script>
</body>
</html>
