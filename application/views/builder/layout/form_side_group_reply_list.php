<?php extract($item['data']); ?>
<div class="col-sm-12 form-validation-unit">
	<?=get_admin_form_list_item($reply_content, 'side', false, true)?>
    <div class="input-group input-group-merge">
        <?=get_admin_form_ico($reply_content)?>
        <div class="form-floating form-floating-outline">
            <?php
                echo form_textarea(
                    [
                        'name' => $reply_content['field'],
                        'id' => $reply_content['id'],
                        'rows' => $reply_content['attributes']['rows']
                    ],
                    set_admin_form_value($reply_content['field'], $reply_content['default'], null),
                    $reply_content['attributes']
                );
                echo form_label(lang($reply_content['label']), $reply_content['id']);
            ?>
        </div>
    </div>
    <?=get_admin_form_text($reply_content)?>
</div>
