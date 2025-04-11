<div class="col-sm-12 form-validation-unit">
    <div class="input-group input-group-merge">
        <?=get_admin_form_ico($item)?>
        <div class="form-floating form-floating-outline">
            <?php
                echo form_input([
                    'name' => $item['field'],
                    'id' => $item['id'],
                ], $item['default'], $item['attributes']);
            ?>
            <?=form_label(lang($item['label']), $item['id'])?>
        </div>
    </div>
	<?=get_admin_form_text($item)?>
</div>
