<?php
    echo form_open_multipart('', [
        'id' => 'formRecord',
        'name' => 'formRecord',
        'class' => "add-new-record needs-validation form-type-page form-type-{$formType} form-subtype-{$formSubType}",
        'data-no-submit' => 'true',
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

	// 탭 정의(tabs_{엔티티}_config, resolve_form_tabs). 헤더를 숨긴 탭(visible=false)도 내용은 그린다
	// — 화면 스크립트가 FORM_DATA 의 모든 필드를 찾기 때문. 그 탭의 값은 서버가 받지 않는다 (MY_Builder_API::setHiddenTabFields)
	$tabs = $formData['tabs'] ?? [];
	$activeKey = null;
	foreach ($tabs as $tab) {
		if($tab['visible']) { $activeKey = $tab['key']; break; }
	}
	$tabId = function ($key) {
		return 'form-tab-'.preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$key);
	};
?>
<div class="row">
    <div class="nav-align-top mb-6">
        <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
            <?php foreach ($tabs as $tab): if(!$tab['visible']) continue; ?>
            <?php
                $label = (string)$tab['label'];
                if(sscanf($label, 'lang:%s', $line) === 1) $label = lang($line);
            ?>
            <li class="nav-item mb-1 mb-sm-0">
                <button
                    type="button"
                    class="nav-link<?=$tab['key'] === $activeKey ? ' active' : ''?>"
                    role="tab"
                    data-bs-toggle="tab"
                    data-bs-target="#<?=$tabId($tab['key'])?>"
                    aria-controls="<?=$tabId($tab['key'])?>"
                    aria-selected="<?=$tab['key'] === $activeKey ? 'true' : 'false'?>">
                    <?php if($tab['icon'] !== ''): ?><i class="tf-icons <?=html_escape($tab['icon'])?> me-2"></i><?php endif; ?>
                    <?=html_escape($label)?>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content">
            <?php foreach ($tabs as $tab): ?>
            <div class="tab-pane<?=$tab['key'] === $activeKey ? ' show active' : ' fade'?>" id="<?=$tabId($tab['key'])?>" role="tabpanel">
                <?php builder_view("{$platformName}/layout/form_inputs", [
                    'formData' => $formData['fields'][$tab['key']] ?? [],
                    'formType' => $formType,
                    'formSubType' => $formSubType,
                ]); ?>
            </div>
            <?php endforeach; ?>
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
            <button type="button" class="btn btn-outline-dark waves-effect" data-action="history-back"><?=lang('List')?></button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary waves-effect waves-light"><?=lang('Submit')?></button>
        <?php if(in_array('delete', $actions)): ?>
            <button type="button" class="btn btn-outline-danger btn-delete-event btn-delete d-none"><?=lang('Delete')?></button>
        <?php endif; ?>
    </div>
</div>
<?=form_close();?>
