<?php
if (!defined("BASEPATH")) exit("No direct script access allowed");

class File_system
{
    protected $CI;
    protected bool $fileModelLoaded = false;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->fileModelLoaded = $this->CI->load->is_loaded('Model_File');
    }

    protected function uploader($name, $_files = null)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');

        $response = [
            'result' => true,
            'data' => [],
            'code' => DATA_UPLOADED,
            'message' => 'success',
        ];

        if (is_null($_files)) $_files = $_FILES;

        if (array_key_exists($name, $_files) && $_files[$name] && $_files[$name]['name']) {
            $uploadPath = 'public/uploads/' . $this->CI->router->class . '/' . date('Y') . '/';
            if (!make_directory($uploadPath)) throw new Exception($this->CI->upload->display_errors(), CREATE_FOLDER_FAIL);

            $this->CI->upload->initialize(
                array_merge(
                    $this->CI->config->item($name . '_upload_config') ?: $this->CI->config->item('base_upload_config'),
                    [
                        'upload_path' => $uploadPath,
                    ]
                )
            );

            if (is_array($_files[$name]['name'])) {
                $file_names = $_files[$name]['name'];

                for ($i = 0; $i < count($file_names); $i++) {
                    $_FILES[$name] = [
                        'name' => $_files[$name]['name'][$i],
                        'type' => $_files[$name]['type'][$i],
                        'tmp_name' => $_files[$name]['tmp_name'][$i],
                        'error' => $_files[$name]['error'][$i],
                        'size' => $_files[$name]['size'][$i],
                    ];

                    try {
                        if (!$this->CI->upload->do_upload($name)) throw new Exception($this->CI->upload->display_errors(), UPLOAD_FILE_FAIL);

                        $data = $this->CI->upload->data();
                        $key = $this->CI->Model_File->addData(array_merge($data,
                            ['file_link' => get_filepath_from_link($data['full_path'])]
                        ), false);
                        if (!$key) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);

                        $response['data'][] = [
                            'file_id' => $key,
                            'attach_cd' => $this->getAttachCd($this->CI->upload->data()['file_ext']),
                        ];
                    } catch (Exception $e) {
                        return [
                            'result' => false,
                            'data' => [],
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            } else {
                $_FILES = $_files;

                try {
                    if (!$this->CI->upload->do_upload($name)) throw new Exception($this->CI->upload->display_errors(), UPLOAD_FILE_FAIL);

                    $key = $this->CI->Model_File->addData($this->CI->upload->data(), false);
                    if (!$key) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);

                    $response['data'][] = [
                        'file_id' => $key,
                        'attach_cd' => $this->getAttachCd($this->CI->upload->data()['file_ext']),
                    ];
                } catch (Exception $e) {
                    return [
                        'result' => false,
                        'data' => [],
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } else {
            $response['result'] = false;
            $response['code'] = UPLOAD_DATA_NOT_EXIST;
            $response['message'] = 'empty';
        }

        return $response;
    }

    public function downloader($key = 0)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');

        $response = [
            'result' => false,
            'data' => [],
            'code' => ERROR_DOWNLOAD_NOTDATA,
            'message' => 'empty',
        ];

        if (!$key) return $response;

        $data = $this->CI->Model_File->getDataWhere([], ['file_id' => $key]);
        if (!$data) return $response;

        if (!is_file($data->full_path)) {
            $response['code'] = ERROR_DOWNLOAD_NOTFILE;
            return $response;
        }

        try {
            $this->CI->Model_File->modNumb('download_cnt', 1, ['file_id' => $key]);
            force_download($data->client_name, file_get_contents($data->full_path));
        } catch (Exception $e) {
            log_message('error', 'downloader : modNumb error ' . $this->CI->db->last_query());
            return [
                'result' => false,
                'data' => [],
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return true;
    }

    function getAttachCd($filename)
    {
        $attach_cd = '';
        if (is_video_file_ext($filename)) {
            $attach_cd = 'ATT002';
        }
        if (is_image_file_ext($filename)) {
            $attach_cd = 'ATT003';
        }
        if (!$attach_cd) $attach_cd = 'ATT001';

        return $attach_cd;
    }

    protected function getFileLink($dto)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');


        $data = $this->getFileData($dto);
        if ($data) {
            return !empty($data->file_link)?$data->file_link:get_filepath_from_link($data->full_path);
        } else {
            return '';
        }
    }

    protected function getFileData($dto)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');

        return $this->CI->Model_File->getData([], $dto);
    }

    protected function getFileList($dto)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');

        return $this->CI->Model_File->getList([], $dto);
    }

    protected function delFileData($dto)
    {
        if(!$this->fileModelLoaded) show_error('File Model not loaded');

        $fileData = $this->getFileData($dto);
        if(!$fileData) $this->CI->response(['code' => UPLOAD_DATA_NOT_EXIST]);

        $this->CI->Model_File->delData($dto);
        if(!unlink($fileData->full_path)) $this->CI->response(['code' => INTERNAL_SERVER_ERROR]);
    }

    public function makeFolderZipData($path): array
    {
        if (!$path) {
            throw new Exception('path 값이 없습니다.');
        }

        if (!str_contains($path, FCPATH)) {
            $path = FCPATH . ltrim($path, '/\\');
        }

        $safePath = get_safe_path($path);

        if (!$safePath || !is_dir($safePath)) {
            throw new Exception('디렉토리가 존재하지 않습니다.');
        }

        if (realpath($safePath) === realpath(FCPATH)) {
            throw new Exception('최상위 디렉토리는 다운로드할 수 없습니다.');
        }

        $this->CI->load->library('zip');

        $this->CI->zip->clear_data();

        $pathWithSlash = rtrim($safePath, '/\\') . DIRECTORY_SEPARATOR;

        /**
         * ZIP 내부에 최상위 폴더명을 포함하지 않고 내용물만 추가
         */
        $result = $this->CI->zip->read_dir($pathWithSlash, false, $pathWithSlash);

        if (!$result) {
            throw new Exception('ZIP 생성에 실패했습니다.');
        }

        if ($this->CI->zip->entries <= 0) {
            throw new Exception('압축할 파일이 없습니다.');
        }

        $zipData = $this->CI->zip->get_zip();

        if ($zipData === false) {
            throw new Exception('ZIP 데이터를 생성할 수 없습니다.');
        }

        return [
            'name' => basename($safePath) . '.zip',
            'data' => $zipData,
        ];
    }

    protected function downloadFolder($path): void
    {
        if (!$path) {
            show_error('path 값이 없습니다.');
        }

        if(!str_contains($path, FCPATH)) {
            $path = FCPATH . ltrim($path, '/\\');
        }

        $safePath = get_safe_path($path);

        if (!$safePath || !is_dir($safePath)) {
            show_error('디렉토리가 존재하지 않습니다.');
        }

        if (realpath($safePath) === realpath(FCPATH)) {
            show_error('최상위 디렉토리는 다운로드할 수 없습니다.');
        }

        $this->CI->load->library('zip');

        $this->CI->zip->clear_data();

        $pathWithSlash = rtrim($safePath, '/\\') . DIRECTORY_SEPARATOR;

        /**
         * ZIP 내부에 최상위 폴더명을 포함하지 않고 내용물만 추가
         */
        $result = $this->CI->zip->read_dir($pathWithSlash, false, $pathWithSlash);

        if (!$result) {
            show_error('ZIP 생성에 실패했습니다.');
        }

        if ($this->CI->zip->entries <= 0) {
            show_error('압축할 파일이 없습니다.');
        }

        $downloadName = basename($safePath) . '.zip';

        $this->CI->zip->download($downloadName);
    }
}
