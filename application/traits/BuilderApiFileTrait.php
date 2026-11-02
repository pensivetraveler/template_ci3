<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 첨부 파일 올리기·순서·삭제 (MY_Builder_API)
 */
trait BuilderApiFileTrait
{
    protected function uploadFileInList($dto, $model = null)
    {
        if(is_null($model)) $model = $this->Model;
        $key = null;
        try {
            $uploadDir = $this->router->class.'/'.date('Y').'/';

            $files = $_FILES;
            foreach ($model->fileList as $key) {
                if(is_file_posted($key)) {
                    $config = $this->config->item($this->router->class . '_' . $key . '_upload_config')
                        ?: $this->config->item($key . '_upload_config')
                            ?: $this->config->item('base_upload_config');

                    if(!array_key_exists('allowed_types', $config))
                        throw new Exception('Upload config is not defined : '.$key, UPLOAD_FILE_FAIL);

                    // 업로드 설정 private: 웹으로 바로 열리지 않는 곳에 두고 downloader 로만 받는다
                    $uploadPath = (empty($config['private']) ? 'public/uploads/' : 'application/uploads/').$uploadDir;
                    if(!make_directory($uploadPath)) throw new Exception($this->upload->display_errors(), CREATE_FOLDER_FAIL);

                    $this->upload->initialize(
                        array_merge(
                            $config,
                            [
                                'upload_path' => $uploadPath,
                            ]
                        )
                    );

                    if(gettype($files[$key]['name']) === 'string') {
                        if(!$this->upload->do_upload($key)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);
                        $dto[$key] = $this->Model_File->addData($this->upload->data(), false);
                        if(!$dto[$key]) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);
                    }else{
                        foreach ($files[$key]['name'] as $idx => $val) {
                            $_FILES[$key]['name'] = $files[$key]['name'][$idx];
                            $_FILES[$key]['type'] = $files[$key]['type'][$idx];
                            $_FILES[$key]['tmp_name'] = $files[$key]['tmp_name'][$idx];
                            $_FILES[$key]['error'] = $files[$key]['error'][$idx];
                            $_FILES[$key]['size'] = $files[$key]['size'][$idx];

                            if(!$this->upload->do_upload($key)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);
                            $dto[$key][$idx] = $this->Model_File->addData($this->upload->data(), false);
                            if(!$dto[$key][$idx]) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => $key,
                'type' => 'upload error',
            ], [
                'code' => $e->getCode(),
                'msg' => strip_tags($e->getMessage()),
                'data' => $_FILES,
            ]);
        }

        return $dto;
    }

    /**
     * 첨부 순서 저장: PATCH /{엔티티}/reorder/{행 id}?type={첨부 종류}, 본문 file_ids[] = 화면 목록 순서 전체
     * 실제 저장은 엔티티의 reorderFiles() 가 한다 (예: Articles 의 article_attachment.sort_order).
     * 전에는 GET 에서 값을 찾고(화면은 PATCH 본문으로 보냄) 아무것도 저장하지 않았다
     */
    public function reorder_patch($key = 0)
    {
        $type = (string)($this->input->get('type') ?? '');
        $fileIds = $this->input->patch('file_ids') ?? [];
        if(is_string($fileIds)) $fileIds = explode(',', $fileIds);
        $fileIds = array_values(array_map('strval', (array)$fileIds));

        if($type === '' || !count($fileIds)) $this->response(['code' => EMPTY_REQUIRED_DATA]);
        if(count(array_unique($fileIds)) !== count($fileIds) || count(array_filter($fileIds, 'ctype_digit')) !== count($fileIds)) {
            $this->response(['code' => BAD_REQUEST], RestController::HTTP_BAD_REQUEST);
        }

        if(!$this->reorderFiles($key, $type, $fileIds)) {
            $this->response(['code' => BAD_REQUEST], RestController::HTTP_BAD_REQUEST);
        }
        $this->response(['code' => DATA_PROCESSED]);
    }

    /**
     * 첨부 순서 저장. 처리했으면 true, 모르는 type 이면 false(400).
     * $fileIds 가 그 행의 첨부 전체와 같은지 확인한 뒤 순서를 저장한다
     */
    protected function reorderFiles($key, string $type, array $fileIds): bool
    {
        return false;
    }

    public function deleteRepeater_patch($key = 0)
    {
        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    /**
     * 첨부 파일 삭제: PATCH /{엔티티}/deleteFile/{행 id}?type={파일 필드}, 본문 file_id
     * 그 행의 파일 필드에 있는 파일만 지우고, 행의 컬럼에서도 file_id 를 뺀다
     */
    public function deleteFile_patch($key = 0)
    {
        $type = $this->input->get('type') ?? null;
        $file_id = $this->input->patch('file_id') ?? null;
        if(!$type || !$file_id) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        $model = null;
        foreach ($this->getModelPipeline() as $modelName) {
            if(property_exists($this, $modelName) && $this->{$modelName} && in_array($type, $this->{$modelName}->fileList, true)) {
                $model = $this->{$modelName};
                break;
            }
        }
        if(!$model) {
            // 파일 필드가 아닌 첨부(별도 테이블 등)는 엔티티의 deleteOtherFile() 이 처리
            if(!$this->deleteOtherFile($key, $type, (string)$file_id)) {
                $this->response(['code' => BAD_REQUEST], RestController::HTTP_BAD_REQUEST);
            }
            $this->response(['code' => DATA_DELETED]);
        }

        $idData = $this->checkIdentifierExist($key, false, $model);
        if(!count($idData)) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        // 미사용(use_yn='N') 행도 관리 화면에서 수정하므로 use_yn 기본 조건 없이 조회·수정 (삭제된 행은 그대로 제외)
        $useUseYnAsBase = $model->useUseYnAsBase;
        $model->useUseYnAsBase = false;

        $row = $model->getDataWhere([$type], $idData);
        $current = is_object($row) ? ($row->{$type} ?? null) : $row;
        $fileIds = $this->parseFileColumnValue($current);
        if(!in_array((string)$file_id, $fileIds, true)) {
            $model->useUseYnAsBase = $useUseYnAsBase;
            $this->response(['code' => DATA_NOT_EXIST], RestController::HTTP_NOT_FOUND);
        }

        $remain = array_values(array_diff($fileIds, [(string)$file_id]));
        $this->modData([$type => count($remain) ? implode(',', $remain) : 0], $idData, false, $model);
        $model->useUseYnAsBase = $useUseYnAsBase;

        $this->delFileData(['file_id' => $file_id]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    /**
     * 모델 파일 필드가 아닌 첨부 삭제 (예: 게시글 첨부 테이블). 처리했으면 true, 모르는 type 이면 false(400).
     * 그 행 소속 파일인지 확인한 뒤 delFileData() 로 지운다. 소속이 아니면 404 로 응답
     */
    protected function deleteOtherFile($key, string $type, string $fileId): bool
    {
        return false;
    }

    /**
     * 파일 컬럼 값(file_id 하나, 'id,id', JSON 배열)을 file_id 문자열 목록으로
     */
    protected function parseFileColumnValue($value): array
    {
        if(is_array($value)) {
            $list = $value;
        }else{
            $value = trim((string)$value);
            if($value === '' || $value === '0') return [];
            $decoded = json_decode($value, true);
            $list = is_array($decoded) ? $decoded : explode(',', $value);
        }
        return array_values(array_filter(array_map(function ($v) {
            return trim((string)$v);
        }, $list), function ($v) {
            return $v !== '' && $v !== '0';
        }));
    }
}
