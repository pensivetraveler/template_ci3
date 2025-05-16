<ul class="menu-inner py-1">
	<?php foreach ($menus as $menuName=>$menu): ?>
	<!-- Page -->
	<li class="menu-item <?=implode(' ', $menu['className'])?>">
		<a href="<?=$menu['href']?>" class="menu-link <?=$menu['subMenuExist']?'menu-toggle waves-effect':''?>" <?=$menu['subMenuExist']?'':'target="'.$menu['target'].'"'?>>
			<?php if($menu['icon']): ?><i class="menu-icon tf-icons <?=$menu['icon']?>"></i><?php endif; ?>
			<div data-i18n="<?=lang('nav.'.$menu['title'])?>"><?=lang('nav.'.$menu['title'])?></div>
		</a>
		<?php if($menu['subMenuExist']): ?>
		<ul class="menu-sub">
			<?php
				foreach ($menu['subMenu'] as $submenuName=>$submenu):
					$submenuHref = $submenu['route'] . '?' . http_build_query($submenu['params']);
					$active = is_admin_active_page($submenu)?'active':'';
			?>
			<li class="menu-item <?=$active?>">
				<a href="<?=$submenuHref?>" class="menu-link">
					<?php if($submenu['icon']): ?><i class="menu-icon tf-icons <?=$submenu['icon']?> me-2"></i><?php endif; ?>
					<div data-i18n="<?=lang('nav.'.$submenu['title'])?>"><?=lang('nav.'.$submenu['title'])?></div>
				</a>
			</li>
			<?php
				endforeach;
			?>
		</ul>
		<?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>
