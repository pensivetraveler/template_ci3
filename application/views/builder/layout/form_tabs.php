<?php
    echo form_open_multipart('', [
        'id' => 'formRecord',
        'name' => 'formRecord',
        'class' => "add-new-record needs-validation form-type-page form-type-{$formType} form-subtype-{$formSubType}",
        'onsubmit' => 'return false',
    ], [
        '_mode' => $this->router->method,
        '_event' => '',
    ]);

	if(!is_empty($formData, 'hiddens') && count($formData['hiddens']) > 0) {
		foreach ($formData['hiddens'] as $item) :
			echo form_input(
					[
							'type' => $item['type'],
							'name' => $item['field'],
							'id' => $item['id'],
					],
					set_admin_form_value($item['field'], $item['default'], null),
					$item['attributes'],
			);
		endforeach;
	}
?>
<div class="row">
    <div class="nav-align-top mb-6">
        <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
            <li class="nav-item mb-1 mb-sm-0">
                <button
                    type="button"
                    class="nav-link active"
                    role="tab"
                    data-bs-toggle="tab"
                    data-bs-target="#navs-pills-justified-home"
                    aria-controls="navs-pills-justified-home"
                    aria-selected="true">
                    <i class="tf-icons ri-home-smile-line me-2"></i> 기본
                </button>
            </li>
            <li class="nav-item mb-1 mb-sm-0">
                <button
                    type="button"
                    class="nav-link"
                    role="tab"
                    data-bs-toggle="tab"
                    data-bs-target="#navs-pills-justified-profile"
                    aria-controls="navs-pills-justified-profile"
                    aria-selected="false">
					<i class="tf-icons ri-image-line me-2"></i> 배너관리
                </button>
            </li>
            <li class="nav-item">
                <button
                    type="button"
                    class="nav-link"
                    role="tab"
                    data-bs-toggle="tab"
                    data-bs-target="#navs-pills-justified-messages"
                    aria-controls="navs-pills-justified-messages"
                    aria-selected="false">
					<i class="tf-icons ri-paint-brush-line me-2"></i> 색상 및 폰트
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="navs-pills-justified-home" role="tabpanel">
                <?php builder_view("{$platformName}/layout/form_inputs", [
                    'formData' => $formData['fields'][0],
                    'formType' => $formType,
                    'formSubType' => $formSubType,
                ]); ?>
            </div>
            <div class="tab-pane fade" id="navs-pills-justified-profile" role="tabpanel">
				<?php builder_view("{$platformName}/layout/form_inputs", [
						'formData' => $formData['fields'][1],
						'formType' => $formType,
						'formSubType' => $formSubType,
				]); ?>
            </div>
            <div class="tab-pane fade" id="navs-pills-justified-messages" role="tabpanel">
				<?php builder_view("{$platformName}/layout/form_inputs", [
						'formData' => $formData['fields'][2],
						'formType' => $formType,
						'formSubType' => $formSubType,
				]); ?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-6 text-start">
        <?php foreach ($buttons as $button=>$attr): ?>
            <button type="button" class="btn btn-outline-dark waves-effect btn-<?=$button?>"><?=lang($attr['text'])?></button>
        <?php endforeach; ?>
    </div>
    <div class="col-sm-6 text-end">
        <?php if(in_array('list', $actions)): ?>
            <button type="button" class="btn btn-outline-dark waves-effect" onclick="<?=WEB_HISTORY_BACK?>"><?=lang('List')?></button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary waves-effect waves-light"><?=lang('Submit')?></button>
        <?php if(in_array('delete', $actions)): ?>
            <button type="button" class="btn btn-outline-danger btn-delete-event btn-delete d-none"><?=lang('Delete')?></button>
        <?php endif; ?>
    </div>
</div>
<?=form_close();?>
