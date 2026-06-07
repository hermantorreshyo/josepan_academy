      </div><!-- /container -->
    </main>
  </div><!-- /shell -->

  <nav class="tabbar">
    <?php
      $a = $pageActive ?? '';
      echo '<a class="'.($a==='cursos'?'active':'').'" href="index.php"><span>📚</span>Cursos</a>';
      echo '<a class="'.($a==='biblioteca'?'active':'').'" href="biblioteca.php"><span>📁</span>Docs</a>';
      echo '<a class="'.($a==='perfil'?'active':'').'" href="perfil.php"><span>🎖️</span>Perfil</a>';
      if (is_admin()) echo '<a class="'.($a==='admin'?'active':'').'" href="admin/index.php"><span>📊</span>Admin</a>';
    ?>
  </nav>
</div>
<?php if (!empty($pageScripts)): foreach ($pageScripts as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; endif; ?>
<?php if (!empty($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
