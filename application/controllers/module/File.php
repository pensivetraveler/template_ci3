<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class File extends Common
{
    /**
     * 편집을 허용할 최상위 디렉토리
     *
     * 예:
     * /var/www/html/templates/
     *
     * 이 폴더 밖으로는 접근 불가하게 막는다.
     */
    private $basePath;

    /**
     * 편집 가능한 확장자
     */
    private $editableExtensions = [
        'json',
        'css',
        'js',
        'html',
        'txt'
    ];

    /**
     * 업로드 가능한 이미지 확장자
     */
    private $imageExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg'
    ];

    public function __construct()
    {
        $this->accessWays = ['ajax'];

        parent::__construct();

        $this->basePath = FCPATH;

        $this->output->set_content_type('application/json', 'utf-8');

        $this->load->helper(['file', 'directory']);
        $this->load->library('zip');
    }

    /**
     * 파일 트리 조회
     *
     * GET /module/file/tree
     * GET /module/file/tree?dir=sample1
     */
    public function tree()
    {
        $dir = $this->input->get('dir', true);
        $dir = $dir ? trim($dir, '/') : '';

        $targetPath = $this->basePath . $dir;

        $safePath = $this->getSafePath($targetPath, true);

        if (!$safePath) {
            return $this->json_response(false, '잘못된 디렉토리 경로입니다.');
        }

        if (!is_dir($safePath)) {
            return $this->json_response(false, '디렉토리가 존재하지 않습니다.');
        }

        $tree = $this->scanDirectory($safePath, realpath($this->basePath));

        return $this->json_response(true, 'success', [
            'base_dir' => $dir,
            'tree' => $tree
        ]);
    }

    /**
     * 파일 내용 읽기
     *
     * GET /module/file/read?path=sample1/config.json
     */
    public function read()
    {
        $path = $this->input->get('path', true);

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');

        $safePath = $this->getSafePath($targetPath, false);

        if (!$safePath) {
            return $this->json_response(false, '잘못된 파일 경로입니다.');
        }

        if (!is_file($safePath)) {
            return $this->json_response(false, '파일이 존재하지 않습니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext)) {
            return $this->json_response(false, '편집할 수 없는 파일 형식입니다.');
        }

        $content = file_get_contents($safePath);

        if ($content === false) {
            return $this->json_response(false, '파일을 읽을 수 없습니다.');
        }

        return $this->json_response(true, 'success', [
            'path' => $this->normalizeRelativePath($safePath),
            'filename' => basename($safePath),
            'extension' => $ext,
            'content' => $content
        ]);
    }

    /**
     * 파일 저장
     *
     * POST /module/file/save
     *
     * JSON Body:
     * {
     *   "path": "sample1/config.json",
     *   "content": "{...}"
     * }
     */
    public function save()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';
        $content = isset($data['content']) ? $data['content'] : '';

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');

        /**
         * 저장 대상 파일이 아직 존재하지 않을 수도 있으므로
         * 파일 자체 realpath()가 아니라 dirname 기준으로 검증한다.
         */
        $safeDir = $this->getSafePath(dirname($targetPath), true);

        if (!$safeDir) {
            return $this->json_response(false, '잘못된 저장 경로입니다.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext)) {
            return $this->json_response(false, '저장할 수 없는 파일 형식입니다.');
        }

        /**
         * JSON 저장 전 문법 검사
         */
        if ($ext === 'json') {
            json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json_response(false, 'JSON 문법 오류: ' . json_last_error_msg());
            }
        }

        /**
         * 기존 파일이 있으면 백업 생성
         */
        if (is_file($targetPath)) {
            $backupResult = $this->backupFile($targetPath);

            if (!$backupResult) {
                return $this->json_response(false, '백업 파일 생성에 실패했습니다.');
            }
        }

        $result = file_put_contents($targetPath, $content, LOCK_EX);

        if ($result === false) {
            return $this->json_response(false, '파일 저장에 실패했습니다.');
        }

        return $this->json_response(true, '저장되었습니다.', [
            'path' => $this->normalizeRelativePath($targetPath),
            'bytes' => $result
        ]);
    }

    /**
     * 이미지 파일 조회용 정보
     *
     * GET /module/file/image_info?path=sample1/images/logo.png
     */
    public function image_info()
    {
        $path = $this->input->get('path', true);

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');
        $safePath = $this->getSafePath($targetPath, false);

        if (!$safePath || !is_file($safePath)) {
            return $this->json_response(false, '이미지 파일이 존재하지 않습니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($ext)) {
            return $this->json_response(false, '이미지 파일이 아닙니다.');
        }

        return $this->json_response(true, 'success', [
            'path' => $this->normalizeRelativePath($safePath),
            'filename' => basename($safePath),
            'extension' => $ext,
            'url' => base_url($this->normalizeRelativePath($safePath)) . '?t=' . time()
        ]);
    }

    /**
     * 이미지 업로드 / 교체
     *
     * POST /module/file/upload_image
     *
     * formData:
     * - path: sample1/images/logo.png
     * - image: File
     */
    public function upload_image()
    {
        $path = $this->input->post('path', true);

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        if (empty($_FILES['image'])) {
            return $this->json_response(false, '업로드된 이미지가 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');
        $targetDir = dirname($targetPath);

        $safeDir = $this->getSafePath($targetDir, true);

        if (!$safeDir) {
            return $this->json_response(false, '잘못된 저장 경로입니다.');
        }

        $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($targetExt)) {
            return $this->json_response(false, '허용되지 않는 이미지 확장자입니다.');
        }

        $uploadName = $_FILES['image']['name'];
        $uploadExt = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($uploadExt)) {
            return $this->json_response(false, '업로드할 수 없는 이미지 형식입니다.');
        }

        /**
         * 기존 파일이 있으면 백업
         */
        if (is_file($targetPath)) {
            $backupResult = $this->backupFile($targetPath);

            if (!$backupResult) {
                return $this->json_response(false, '백업 파일 생성에 실패했습니다.');
            }
        }

        /**
         * 확장자를 유지해서 덮어쓰기
         */
        $result = move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);

        if (!$result) {
            return $this->json_response(false, '이미지 저장에 실패했습니다.');
        }

        return $this->json_response(true, '이미지가 저장되었습니다.', [
            'path' => $this->normalizeRelativePath($targetPath),
            'url' => base_url('templates/' . $this->normalizeRelativePath($targetPath)) . '?t=' . time()
        ]);
    }

    /**
     * 파일 생성
     *
     * POST /module/file/create
     *
     * JSON Body:
     * {
     *   "path": "sample1/new-style.css",
     *   "content": ""
     * }
     */
    public function create()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';
        $content = isset($data['content']) ? $data['content'] : '';

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');
        $targetDir = dirname($targetPath);

        $safeDir = $this->getSafePath($targetDir, true);

        if (!$safeDir) {
            return $this->json_response(false, '잘못된 생성 경로입니다.');
        }

        if (file_exists($targetPath)) {
            return $this->json_response(false, '이미 같은 이름의 파일이 존재합니다.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
            return $this->json_response(false, '생성할 수 없는 파일 형식입니다.');
        }

        if ($ext === 'json' && trim($content) === '') {
            $content = "{}";
        }

        $result = file_put_contents($targetPath, $content, LOCK_EX);

        if ($result === false) {
            return $this->json_response(false, '파일 생성에 실패했습니다.');
        }

        return $this->json_response(true, '파일이 생성되었습니다.', [
            'path' => $this->normalizeRelativePath($targetPath)
        ]);
    }

    /**
     * 파일 삭제
     *
     * POST /module/file/delete
     *
     * JSON Body:
     * {
     *   "path": "sample1/style.css"
     * }
     */
    public function delete()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';

        if (!$path) {
            return $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->basePath . ltrim($path, '/');
        $safePath = $this->getSafePath($targetPath, false);

        if (!$safePath || !is_file($safePath)) {
            return $this->json_response(false, '파일이 존재하지 않습니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
            return $this->json_response(false, '삭제할 수 없는 파일 형식입니다.');
        }

        $this->backupFile($safePath);

        $result = unlink($safePath);

        if (!$result) {
            return $this->json_response(false, '파일 삭제에 실패했습니다.');
        }

        return $this->json_response(true, '파일이 삭제되었습니다.');
    }

    /**
     * 디렉토리 재귀 스캔
     *
     * 정렬 기준:
     * 1. directory 먼저
     * 2. file 나중
     * 3. 각각 이름 기준 오름차순 정렬
     */
    private function scanDirectory($dir, $baseRealPath)
    {
        $directories = [];
        $files = [];

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            /**
             * 숨김 파일 제외
             */
            if (substr($item, 0, 1) === '.') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $directories[] = [
                    'type' => 'directory',
                    'name' => $item,
                    'path' => $this->normalizeRelativePath($fullPath),
                    'children' => $this->scanDirectory($fullPath, $baseRealPath)
                ];

                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            /**
             * 화면에 보여줄 파일만 제한
             */
            if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
                continue;
            }

            $files[] = [
                'type' => 'file',
                'name' => $item,
                'path' => $this->normalizeRelativePath($fullPath),
                'extension' => $ext,
                'editable' => $this->isEditableExtension($ext),
                'image' => $this->isImageExtension($ext)
            ];
        }

        /**
         * 디렉토리 이름순 정렬
         */
        usort($directories, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        /**
         * 파일 이름순 정렬
         */
        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        /**
         * directory 먼저, file 나중
         */
        return array_merge($directories, $files);
    }

    /**
     * 경로 보안 검증
     *
     * $mustBeDirectory = true 이면 디렉토리 기준 검증
     * $mustBeDirectory = false 이면 파일 기준 검증
     */
    private function getSafePath($path, $mustBeDirectory = false)
    {
        return get_safe_path($path, $this->basePath, $mustBeDirectory);
    }

    /**
     * 절대 경로를 basePath 기준 상대 경로로 변환
     */
    private function normalizeRelativePath($fullPath)
    {
        $baseRealPath = realpath($this->basePath);
        $fullRealPath = realpath($fullPath);

        if (!$fullRealPath) {
            $fullRealPath = $fullPath;
        }

        $relativePath = str_replace($baseRealPath, '', $fullRealPath);
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = trim($relativePath, '/');

        return $relativePath;
    }

    private function isEditableExtension($ext)
    {
        return in_array(strtolower($ext), $this->editableExtensions);
    }

    private function isImageExtension($ext)
    {
        return in_array(strtolower($ext), $this->imageExtensions);
    }

    /**
     * 저장/삭제 전 백업
     */
    private function backupFile($targetPath)
    {
        if (!is_file($targetPath)) {
            return true;
        }

        $backupBasePath = FCPATH . 'file_backups/' . date('Ymd') . '/';

        if (!is_dir($backupBasePath)) {
            if (!mkdir($backupBasePath, 0755, true)) {
                return false;
            }
        }

        $relativePath = $this->normalizeRelativePath($targetPath);
        $safeName = str_replace(['/', '\\'], '__', $relativePath);

        $backupFile = $backupBasePath . $safeName . '.' . date('His') . '.bak';

        return copy($targetPath, $backupFile);
    }
}
