<div id="sidebar">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="d-flex justify-content-center align-items-center">
                <div class="logo">
                    <a href="<?= site_url('panel') ?>"><?= setting('site.site_title') ?></a>
                </div>

                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <?php foreach (sidebarMenus() as $menu) : ?>
                    <li class="sidebar-item <?= $menu['module'] == $module ? 'active submenu-open' : '' ?> <?= isset($menu['children']) && count($menu['children']) > 0 ? 'has-sub' : '' ?>">
                        <a href="<?= $menu['url'] ? site_url($menu['url']) : '#' ?>" target="<?= $menu['target'] ?? '_self' ?>" class='sidebar-link'>
                            <i class="<?= $menu['icon'] ?>"></i>
                            <span><?= $menu['label'] ?></span>
                        </a>
                        <?php if (isset($menu['children']) && count($menu['children']) > 0) : ?>
                            <ul class="submenu">
                                <?php foreach ($menu['children'] as $child) : ?>
                                    <li class="submenu-item <?= $child['submodule'] == $submodule ? 'active' : '' ?>">
                                        <a href="<?= $child['url'] ? site_url($child['url']) : '#' ?>" target="<?= $child['target'] ?? '_self' ?>">
                                            <i class="<?= $child['icon'] ?>"></i>
                                            <span><?= $child['label'] ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>