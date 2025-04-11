<ul class="menu-inner py-1">
	<?php
		foreach ($this->config->get('admin_nav_side', $this->config->get('builder_nav_side_sample', []), false) as $menuName=>$menu):
			$menu = array_merge(
					$this->config->get('builder_nav_side_base', []),
					$menu
			);

			if(is_admin_active_page($menu)) $menu['className'][] = 'active';
			$subMenuExist = count($menu['subMenu']) > 0;
			$href = $menu['route'] ? $menu['route'] . '?' . http_build_query($menu['params']) : '';
			if($subMenuExist && is_admin_active_page($menu)) $menu['className'][] = 'open';
	?>
	<!-- Page -->
	<li class="menu-item <?=implode(' ', $menu['className'])?>">
		<a href="<?=$href?>" class="menu-link <?=$subMenuExist?'menu-toggle waves-effect':''?>">
			<?php if($menu['icon']): ?><i class="menu-icon tf-icons <?=$menu['icon']?>"></i><?php endif; ?>
			<div data-i18n="<?=lang('nav.'.$menu['title'])?>"><?=lang('nav.'.$menu['title'])?></div>
		</a>
		<?php if($subMenuExist): ?>
		<ul class="menu-sub">
			<?php
				foreach ($menu['subMenu'] as $submenuName=>$submenu):
					$submenuHref = $submenu['route'] . '?' . http_build_query($submenu['params']);
					$active = is_admin_active_page($submenu)?'active':'';
			?>
			<li class="menu-item <?=$active?>">
				<a href="<?=$submenuHref?>" class="menu-link">
					<?php if($submenu['icon']): ?><i class="menu-icon tf-icons <?=$submenu['icon']?>"></i><?php endif; ?>
					<div data-i18n="<?=lang('nav.'.$submenu['title'])?>"><?=lang('nav.'.$submenu['title'])?></div>
				</a>
			</li>
			<?php
				endforeach;
			?>
		</ul>
		<?php endif; ?>
	</li>
	<?php
		endforeach;
	?>
</ul>
