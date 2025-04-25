<?php extract($item['data']); ?>
<div class="row mb-3 form-validation-unit">
    <?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label'])?>
    <div class="col-sm-10">
        <div class="input-group input-group-merge mb-3">
            <ul
                class="list-unstyled mb-0 p-2 bg-lighter rounded-3 d-none"
                id="<?=$reply_list['id']?>">
                <div class="d-flex align-items-center">
                    <div class="badge text-body text-truncate">
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                    </div>
                </div>
            </ul>
            <?=form_label(lang($reply_list['label']), $reply_list['id']);?>
        </div>
        <div class="input-group input-group-merge">
            <?=get_admin_form_ico($reply_content)?>
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
        <?=get_admin_form_text($reply_content)?>
    </div>
</div>
