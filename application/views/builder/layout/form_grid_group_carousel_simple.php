<?php
extract($item['data']);
?>
<div class="col-md-<?=$item['colspan']??6?> mb-6 form-validation-unit">
	<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<div
		class="border border-1 border-input rounded-3 custom-carousel-simple-wrap"
		id="<?=$item['group']?>"
	>
		<input type="file"
			   class="custom-carousel-img-add d-none"
			   id="<?=$item['group']?>-<?=$image['field']?>-add"
			   data-id="<?=$item['group']?>"
			   accept="<?=$image['attributes']['accept']?>"
		>
		<div class="p-3 custom-carousel-list-wrap">
			<ul class="bg-light custom-carousel-list p-2 mb-0 rounded rounded-3"
				id="<?=$item['group']?>-<?=$image['field']?>-list"
				data-carousel-field="<?=$item['group']?>"
				data-carousel-pos="<?=$image['attributes']['carousel_position']?>"
				data-carousel-cnt="0"
			>
				<li class="list-unstyled border-solid border-1 border-secondary p-4 rounded-2 border-dashed custom-carousel-no-items">
					<div class="d-flex align-items-center justify-content-center">
						<span class="text-center"><?=lang('No Carousel Items Yet')?></span>
						<i class="ms-2 ri-emotion-unhappy-line"></i>
					</div>
				</li>
			</ul>
		</div>
		<div class="p-3 pt-0">
			<button type="button" type="button" class="btn btn-secondary waves-effect waves-light w-100 btn-add-carousel"
				data-id="<?=$item['group']?>-<?=$image['field']?>-add"
			>
				<i class="ri-add-line"></i><?=lang('Add Carousel Item')?>
			</button>
		</div>
	</div>
</div>
