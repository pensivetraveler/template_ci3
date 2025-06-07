<ul class="menu-inner py-1">
	<?php foreach ($menus as $menu): ?>
	<!-- Page -->
	<li class="menu-item <?=$menu['listClassName']?>" data-menu-code="<?=$menu['code']?>">
		<a href="<?=get_menu_href($menu['attr']['href'], $menu['params'])?>" class="menu-link <?=$menu['attr']['className']?>" <?=$menu['isSubMenu']?'':'target="'.$menu['attr']['target'].'"'?>>
			<?php if($menu['icon']): ?><i class="menu-icon tf-icons <?=$menu['icon']?> fw-normal"></i><?php endif; ?>
			<div data-i18n="<?=__('nav.'.$menu['title'])?>"><?=__('nav.'.$menu['title'])?></div>
		</a>
		<?php if($menu['isSubMenu']): ?>
		<ul class="menu-sub">
			<?php foreach ($menu['subMenu'] as $submenu): ?>
			<li class="menu-item <?=$submenu['listClassName']?>" data-menu-code="<?=$submenu['code']?>">
				<a href="<?=get_menu_href($submenu['attr']['href'], $submenu['params'])?>" class="menu-link <?=$submenu['attr']['className']?>">
					<?php if($submenu['icon']): ?><i class="menu-icon tf-icons <?=$submenu['icon']?> me-2 fw-normal"></i><?php endif; ?>
					<div data-i18n="<?=__('nav.'.$submenu['title'])?>"><?=__('nav.'.$submenu['title'])?></div>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>
