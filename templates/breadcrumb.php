<?php
/**
 * Componente de Breadcrumb reutilizável
 *
 * Uso nos módulos:
 *   $breadcrumbs = [
 *       ['label' => 'Dashboard', 'url' => $base_path . 'dashboard.php'],
 *       ['label' => 'Clientes',  'url' => $base_path . 'modules/clientes/listar.php'],
 *       ['label' => 'Adicionar'],  // último item não tem url
 *   ];
 *   include __DIR__ . '/../../templates/breadcrumb.php';
 *
 * A variável $breadcrumbs deve estar definida antes de incluir.
 * A variável $page_title_override pode ser definida para sobrescrever o título da página.
 */

if (!isset($breadcrumbs) || !is_array($breadcrumbs) || empty($breadcrumbs)) {
    return;
}

$last = end($breadcrumbs);
$page_label = $last['label'] ?? '';
?>
<nav aria-label="Navegação" class="breadcrumb-nav">
    <ol class="breadcrumb mb-0">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php $isLast = ($index === count($breadcrumbs) - 1); ?>
            <li class="breadcrumb-item <?php echo $isLast ? 'active' : ''; ?>" <?php echo $isLast ? 'aria-current="page"' : ''; ?>>
                <?php if (!$isLast && !empty($crumb['url'])): ?>
                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>">
                        <?php if (!empty($crumb['icon'])): ?>
                            <i class="bi bi-<?php echo htmlspecialchars($crumb['icon']); ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($crumb['label']); ?>
                    </a>
                <?php else: ?>
                    <?php if (!empty($crumb['icon'])): ?>
                        <i class="bi bi-<?php echo htmlspecialchars($crumb['icon']); ?> me-1"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($crumb['label']); ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<style>
.breadcrumb-nav {
    background: rgba(255,255,255,0.6);
    border-radius: 8px;
    padding: 8px 14px;
    margin-bottom: 1rem;
    border: 1px solid var(--premium-border, #e8ecf0);
    backdrop-filter: blur(4px);
}
.breadcrumb-nav .breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
    font-size: 0.85rem;
}
.breadcrumb-nav .breadcrumb-item a {
    color: var(--premium-blue, #0f4c75);
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb-nav .breadcrumb-item a:hover {
    text-decoration: underline;
}
.breadcrumb-nav .breadcrumb-item.active {
    color: var(--premium-text-muted, #6c757d);
    font-weight: 500;
}
.breadcrumb-nav .breadcrumb-item + .breadcrumb-item::before {
    color: #adb5bd;
}
</style>
