<?php
	// 켜져 있으면 변경 여부와 관계없이 항상 Y 전송 (미전송 시 setData 가 N 으로 저장)
	$attributes = $attributes ?? [];
	$attributes['data-detect-changed'] = 0;
?>
<div class="custom-toggle-wrapper" id="<?=$id?>">
    <input type="checkbox" id="<?=$field?>_toggle" name="<?=$name ?? $field?>" value="Y"<?=_attributes_to_string($attributes)?>>
    <label for="<?=$field?>_toggle" class="custom-toggle-label">
        <span class="toggle-txt checked-txt">On</span>
        <span class="toggle-txt unchecked-txt">Off</span>
        <span class="custom-toggle-btn"></span>
    </label>
</div>
