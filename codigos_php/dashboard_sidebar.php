<?php
$dashboardNavigation = $dashboardNavigation ?? [];
$dashboardRoleLabel = $dashboardRoleLabel ?? 'Usuario';
?>
<aside class="dashboard-sidebar" id="dashboardSidebar" aria-label="Navegación principal">
  <div class="dashboard-brand">
    <span class="dashboard-brand-mark" aria-hidden="true">S</span>
    <span class="dashboard-brand-copy"><strong>SENA ServiControl</strong><small><?= htmlspecialchars($dashboardRoleLabel) ?></small></span>
  </div>
  <nav class="dashboard-nav">
    <?php foreach ($dashboardNavigation as $section => $items): ?>
      <div class="dashboard-nav-group">
        <p class="dashboard-nav-heading"><?= htmlspecialchars($section) ?></p>
        <?php foreach ($items as $item): ?>
          <a class="dashboard-nav-link<?= !empty($item['active']) ? ' active' : '' ?>"
             href="<?= htmlspecialchars($item['href']) ?>"
             aria-label="<?= htmlspecialchars($item['label']) ?>"
             title="<?= htmlspecialchars($item['label']) ?>"
             <?= !empty($item['active']) ? 'aria-current="page"' : '' ?>>
            <span class="dashboard-nav-icon" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
            <span class="dashboard-nav-label"><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>
</aside>
<button class="dashboard-sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menú"></button>
