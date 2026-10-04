<?php if(isset($filters) && !empty($filters)): ?>
<div class="card-header border-bottom">
<?=form_open('', [
    'id' => 'formFilter',
    'name' => 'formFilter',
    'class' => 'form-type-filter'
], [
    '_onloaded' => 0,
]); ?>
<h5 class="card-title mb-0"><?=lang('Filters');?></h5>
<?php if(isset($filterHelpBlock) && !empty($filterHelpBlock)) echo get_help_block($filterHelpBlock); ?>
<?php foreach ($filters as $row): ?>
<div class="d-flex justify-content-lg-start align-items-center row gx-5 pt-4 gap-5 gap-md-0">
    <?php
        foreach ($row as $item):
            if($item['type'] === 'common') {
                switch ($item['subtype']) {
                    case 'space' :
	?>
	<div class="col-md-<?=$item['colspan']?> d-sm-block d-none"></div>
	<?php
						break;
                    case 'submit' :
	?>
	<div class="col-md-3 d-flex align-items-center justify-content-end filter-btns">
		<?php if($item['search_btn']): ?>
		<button class="btn btn-primary w-px-100 btn-search" type="button">검색</button>
		<?php endif; ?>
		<?php if($item['reset_btn']): ?>
		<button class="btn btn-outline-primary w-px-100 btn-reset" type="button">초기화</button>
		<?php endif; ?>
	</div>
	<?php
                        break;
                }
            } else {
    ?>
    <div class="col-md-<?=$item['colspan']?>">
        <div class="input-group input-group-merge">
            <div class="form-floating form-floating-outline">
                <?php
                    switch ($item['type']) {
                        case 'password' :
                            echo form_password(
                                [
                                    'name' => $item['name'],
                                    'id' => $item['id'],
                                ],
                                set_admin_form_value($item['field'], $item['default'], null),
                                $item['attributes']
                            );
                            break;
                        case 'checkbox' :
                            echo get_admin_form_choice($item, 'filter');
                            break;
                        case 'radio' :
                            echo get_admin_form_radio($item, 'filter');
                            break;
                        case 'select' :
                            echo form_dropdown(
                                $item['name'],
                                $item['options'] ?? [],
                                set_admin_form_value($item['field'], $item['default'], null),
                                array_merge([
                                    'id' => $item['id'],
                                    'data-style' => 'btn-default'
                                ], $item['attributes'])
                            );
                            break;
                        case 'textarea' :
                            echo form_textarea(
                                [
                                    'name' => $item['name'],
                                    'id' => $item['id'],
                                    'rows' => $item['attributes']['rows']
                                ],
                                set_admin_form_value($item['field'], $item['default'], null),
                                $item['attributes']
                            );
                            break;
                        case 'file' :
                            echo form_upload([
                                'name' => $item['name'],
                                'id' => $item['id'],
                            ], $item['attributes']);
                            break;
                        default :
                            echo form_input(
                                [
                                    'type' => $item['type'],
                                    'name' => $item['name'],
                                    'id' => $item['id'],
                                ],
                                set_admin_form_value($item['field'], $item['default'], null),
                                $item['attributes']
                            );
                    }
                    echo form_label(lang($item['label']), $item['id']);
                ?>
            </div>
        </div>
    </div>
    <?php
            }
        endforeach;
    ?>
</div>
<?php endforeach; ?>
<?=form_close();?>
</div>
<?php endif; ?>
