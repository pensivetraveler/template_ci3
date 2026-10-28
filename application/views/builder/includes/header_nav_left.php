<ul class="menu-inner py-1">
	<?php foreach ($menus as $menu): ?>
	<!-- Page -->
	<li class="menu-item <?=html_escape($menu['listClassName'])?>" data-menu-code="<?=html_escape($menu['code'])?>">
		<a href="<?=html_escape(get_menu_href($menu['attr']['href'], $menu['params']))?>" class="menu-link <?=html_escape($menu['attr']['className'])?>" <?=$menu['isSubMenu']?'':'target="'.html_escape($menu['attr']['target']).'"'?>>
			<?php if($menu['icon']): ?><i class="menu-icon tf-icons <?=html_escape($menu['icon'])?> fw-normal"></i><?php endif; ?>
			<div data-i18n="<?=html_escape(__('nav.'.$menu['title']))?>"><?=html_escape(__('nav.'.$menu['title']))?></div>
		</a>
		<?php if($menu['isSubMenu']): ?>
		<ul class="menu-sub">
			<?php foreach ($menu['subMenu'] as $submenu): ?>
			<li class="menu-item <?=html_escape($submenu['listClassName'])?>" data-menu-code="<?=html_escape($submenu['code'])?>">
				<a href="<?=html_escape(get_menu_href($submenu['attr']['href'], $submenu['params']))?>" class="menu-link <?=html_escape($submenu['attr']['className'])?>">
					<?php if($submenu['icon']): ?><i class="menu-icon tf-icons <?=html_escape($submenu['icon'])?> me-2 fw-normal"></i><?php endif; ?>
					<div data-i18n="<?=html_escape(__('nav.'.$submenu['title']))?>"><?=html_escape(__('nav.'.$submenu['title']))?></div>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>
