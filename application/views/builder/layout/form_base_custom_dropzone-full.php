<!--<div class="row form-validation-unit mb-4" id="--><?php //=$item['field']?><!---dropzone-wrapper">-->
<div class="row form-validation-unit mb-4" id="thumbnail-dropzone-wrapper">
    <div class="col-sm-12">
        <div class="input-group">
            <div class="w-100 h-px-800 border border-input rounded-3 p-4 dropzone-wrapper" id="fullpage-dropzone" data-field="<?=$item['field']?>">
                <div class="dz-message needsclick d-flex justify-content-center align-items-center h-100 m-0">
                    <span class="text-primary">파일을 이곳에 드래그하거나<br>클릭하여 업로드하세요</span>
                </div>
                <div class="fallback">
                    <?php
                    echo form_upload([
                        'name' => $item['field'],
                        'id' => $item['id'],
                    ], $item['attributes']);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
