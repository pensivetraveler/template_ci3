<?php
    foreach ($formData as $row) :
?>
<div class="row">
    <?php
        foreach ($row as $item):
            if(!is_empty($item, 'group') && $item['group'] !== 'base'):
                builder_view("{$platformName}/layout/form_{$formSubType}_group_".$item['view'], ['item' => $item]);
            elseif($item['type'] === 'common'):
				switch ($item['subtype']) {
					case 'space':
						echo "<div class='col-md-{$item['colspan']} d-sm-block d-none'></div>";
						break;
					case 'text':
						$text = lang($item['content']);
						echo "<div class='col-md-{$item['colspan']} d-sm-block'>";
						echo "<h6>{$text}</h6>";
						echo "</div>";
						break;
					case 'divider':
						$text = $item['content']?lang($item['content']):'';
						echo "<div class='divider'><div class='divider-text'>{$text}</div></div>";
						break;
				}
            elseif($item['type'] === 'custom'):
                builder_view("{$platformName}/layout/form_{$formSubType}_custom_".$item['view'], ['item' => $item]);
            else:
	?>
	<div class="col-md-<?=$item['colspan']?> mb-6 form-validation-unit" data-field-name="<?=$item['field']??''?>">
		<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
		<div class="input-group input-group-merge">
			<?php
				echo get_admin_form_ico($item);
				echo get_page_form_input_by_type($item, 'page');
			?>
		</div>
		<?php
				echo get_admin_form_text($item);
				echo get_admin_form_list_item($item, $formSubType);
		?>
	</div>
	<?php
            endif;
        endforeach;
    ?>
</div>
<?php
    endforeach;
?>
