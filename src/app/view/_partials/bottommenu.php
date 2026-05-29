<?php
$menus = sidebarMenus();
$bottomRoot = null;

foreach ($menus as $menu) {
	if (($menu['label'] ?? '') === '_bottommenu') {
		$bottomRoot = $menu;
		break;
	}
}

$bottomItems = $bottomRoot['children'] ?? [];
$bottomItems = array_values(array_filter($bottomItems, static function ($item) {
	return ($item['label'] ?? '') !== '_bottommenu';
}));

$shouldHide = empty($bottomItems);
if (($bottomRoot['label'] ?? '') === '_bottommenu' && in_array(($module ?? ''), ['_bottommenu', 'bottommenu'], true)) {
	$shouldHide = true;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentPath = '/' . ltrim($currentPath, '/');
?>

<?php if (!$shouldHide): ?>
<style>
	#mobile-bottommenu {
		display: none;
	}

	@media (max-width: 991.98px) {
		#mobile-bottommenu {
			position: fixed;
			left: 0;
			right: 0;
			bottom: 0;
			z-index: 1030;
			display: flex;
			align-items: stretch;
			justify-content: space-around;
			gap: 0;
			background: #ffffff;
			border-top: 1px solid #e9ecef;
			box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
            padding-top: env(safe-area-inset-top);
		}

		#mobile-bottommenu a {
			flex: 1;
			min-width: 0;
			text-decoration: none;
			color: #6c757d;
			text-align: center;
			padding: 0.35rem 0.2rem;
			transition: all 0.2s ease;
		}

		#mobile-bottommenu a.active {
			color: #0d6efd;
			background: #e7f1ff;
		}

		#mobile-bottommenu .icon {
			display: block;
			font-size: 1.1rem;
			line-height: 1.2;
		}

		#mobile-bottommenu .label {
			display: block;
			font-size: 0.72rem;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		#main-content {
			padding-bottom: calc(76px + env(safe-area-inset-bottom));
		}
	}
	@media (min-width: 992px) {
		#mobile-bottommenu {
			display: none !important;
		}
	}
</style>

<nav id="mobile-bottommenu" aria-label="Bottom menu">
	<?php foreach ($bottomItems as $item): ?>
		<?php
		$url = $item['url'] ?? '#';
		$normalizedUrl = '/' . ltrim((string) $url, '/');
		$isActive = $item['submodule'] == $submodule;
		?>
		<a
			href="<?= $url && $url !== '#' ? site_url($url) : '#' ?>"
			target="<?= $item['target'] ?? '_self' ?>"
			class="<?= $isActive ? 'active' : '' ?>"
		>
			<span class="icon"><i class="<?= $item['icon'] ?: 'bi bi-circle' ?>"></i></span>
			<span class="label"><?= htmlspecialchars($item['label'] ?? '') ?></span>
		</a>
	<?php endforeach; ?>
</nav>
<?php endif; ?>