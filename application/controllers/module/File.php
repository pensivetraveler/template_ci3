<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class File extends Common
{
    /**
     * 편집을 허용할 최상위 경로 (FCPATH 기준, config: file_editor_roots)
     *
     * 예: ['public/templates/']
     *
     * 이 경로 밖으로는 접근 불가하게 막는다.
     * 파일은 Storage 라이브러리로 읽고 쓴다 (로컬 디스크 / S3)
     */
    private $roots = [];

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

        $this->roots = $this->config->item('file_editor_roots') ?: ['public/'];

        $this->output->set_content_type('application/json', 'utf-8');

        if (!$this->session->userdata('token')) {
            $this->json_response(false, 'Login Needed', [], 401);
        }

        $this->load->helper(['file', 'directory']);
        $this->load->library('storage');
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

        $safePath = $this->getSafePath($dir);

        if (!$safePath) {
            $this->json_response(false, '잘못된 디렉토리 경로입니다.');
        }

        if (!$this->storage->isDir($safePath)) {
            $this->json_response(false, '디렉토리가 존재하지 않습니다.');
        }

        $tree = $this->buildTree($safePath, $this->storage->listFiles($safePath));

        $this->json_response(true, 'success', [
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
            $this->json_response(false, 'path 값이 없습니다.');
        }

        $safePath = $this->getSafePath($path);

        if (!$safePath) {
            $this->json_response(false, '잘못된 파일 경로입니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext)) {
            $this->json_response(false, '편집할 수 없는 파일 형식입니다.');
        }

        $content = $this->storage->read($safePath);

        if ($content === null) {
            $this->json_response(false, '파일이 존재하지 않습니다.');
        }

        $this->json_response(true, 'success', [
            'path' => $safePath,
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
            $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';
        $content = isset($data['content']) ? $data['content'] : '';

        if (!$path) {
            $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->getSafePath($path);

        if (!$targetPath || !$this->storage->isDir(dirname($targetPath))) {
            $this->json_response(false, '잘못된 저장 경로입니다.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext)) {
            $this->json_response(false, '저장할 수 없는 파일 형식입니다.');
        }

        /**
         * JSON 저장 전 문법 검사
         */
        if ($ext === 'json') {
            json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->json_response(false, 'JSON 문법 오류: ' . json_last_error_msg());
            }
        }

        /**
         * 기존 파일이 있으면 백업 생성
         */
        if (!$this->backupFile($targetPath)) {
            $this->json_response(false, '백업 파일 생성에 실패했습니다.');
        }

        $result = $this->storage->write($targetPath, (string)$content);

        if ($result === false) {
            $this->json_response(false, '파일 저장에 실패했습니다.');
        }

        $this->json_response(true, '저장되었습니다.', [
            'path' => $targetPath,
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
            $this->json_response(false, 'path 값이 없습니다.');
        }

        $safePath = $this->getSafePath($path);

        if (!$safePath || !$this->storage->exists($safePath)) {
            $this->json_response(false, '이미지 파일이 존재하지 않습니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($ext)) {
            $this->json_response(false, '이미지 파일이 아닙니다.');
        }

        $this->json_response(true, 'success', [
            'path' => $safePath,
            'filename' => basename($safePath),
            'extension' => $ext,
            'url' => $this->storage->url($safePath) . '?t=' . time()
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
            $this->json_response(false, 'path 값이 없습니다.');
        }

        if (empty($_FILES['image'])) {
            $this->json_response(false, '업로드된 이미지가 없습니다.');
        }

        $targetPath = $this->getSafePath($path);

        if (!$targetPath || !$this->storage->isDir(dirname($targetPath))) {
            $this->json_response(false, '잘못된 저장 경로입니다.');
        }

        $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($targetExt)) {
            $this->json_response(false, '허용되지 않는 이미지 확장자입니다.');
        }

        $uploadName = $_FILES['image']['name'];
        $uploadExt = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

        if (!$this->isImageExtension($uploadExt)) {
            $this->json_response(false, '업로드할 수 없는 이미지 형식입니다.');
        }

        /**
         * 기존 파일이 있으면 백업
         */
        if (!$this->backupFile($targetPath)) {
            $this->json_response(false, '백업 파일 생성에 실패했습니다.');
        }

        /**
         * 확장자를 유지해서 덮어쓰기
         */
        $tmpName = $_FILES['image']['tmp_name'];
        $result = is_uploaded_file($tmpName) && $this->storage->putFile($targetPath, $tmpName);

        if (!$result) {
            $this->json_response(false, '이미지 저장에 실패했습니다.');
        }

        $this->json_response(true, '이미지가 저장되었습니다.', [
            'path' => $targetPath,
            'url' => $this->storage->url($targetPath) . '?t=' . time()
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
            $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';
        $content = isset($data['content']) ? $data['content'] : '';

        if (!$path) {
            $this->json_response(false, 'path 값이 없습니다.');
        }

        $targetPath = $this->getSafePath($path);

        if (!$targetPath || !$this->storage->isDir(dirname($targetPath))) {
            $this->json_response(false, '잘못된 생성 경로입니다.');
        }

        if ($this->storage->exists($targetPath)) {
            $this->json_response(false, '이미 같은 이름의 파일이 존재합니다.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
            $this->json_response(false, '생성할 수 없는 파일 형식입니다.');
        }

        if ($ext === 'json' && trim($content) === '') {
            $content = "{}";
        }

        $result = $this->storage->write($targetPath, (string)$content);

        if ($result === false) {
            $this->json_response(false, '파일 생성에 실패했습니다.');
        }

        $this->json_response(true, '파일이 생성되었습니다.', [
            'path' => $targetPath
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
            $this->json_response(false, '잘못된 요청 형식입니다.');
        }

        $path = isset($data['path']) ? trim($data['path']) : '';

        if (!$path) {
            $this->json_response(false, 'path 값이 없습니다.');
        }

        $safePath = $this->getSafePath($path);

        if (!$safePath || !$this->storage->exists($safePath)) {
            $this->json_response(false, '파일이 존재하지 않습니다.');
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
            $this->json_response(false, '삭제할 수 없는 파일 형식입니다.');
        }

        $this->backupFile($safePath);

        $result = $this->storage->delete($safePath);

        if (!$result) {
            $this->json_response(false, '파일 삭제에 실패했습니다.');
        }

        $this->json_response(true, '파일이 삭제되었습니다.');
    }

    /**
     * 파일 키 목록으로 트리 구성
     *
     * 정렬 기준:
     * 1. directory 먼저
     * 2. file 나중
     * 3. 각각 이름 기준 오름차순 정렬
     */
    private function buildTree($base, array $keys)
    {
        $root = ['dirs' => [], 'files' => []];

        foreach ($keys as $key) {
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));

            /**
             * 화면에 보여줄 파일만 제한
             */
            if (!$this->isEditableExtension($ext) && !$this->isImageExtension($ext)) {
                continue;
            }

            $segments = explode('/', substr($key, strlen($base) + 1));
            $name = array_pop($segments);

            $node =& $root;
            $path = $base;
            foreach ($segments as $segment) {
                $path .= '/' . $segment;
                if (!isset($node['dirs'][$segment])) {
                    $node['dirs'][$segment] = ['path' => $path, 'dirs' => [], 'files' => []];
                }
                $node =& $node['dirs'][$segment];
            }

            $node['files'][] = [
                'type' => 'file',
                'name' => $name,
                'path' => $key,
                'extension' => $ext,
                'editable' => $this->isEditableExtension($ext),
                'image' => $this->isImageExtension($ext)
            ];
            unset($node);
        }

        return $this->flattenTree($root);
    }

    private function flattenTree(array $node)
    {
        $directories = [];
        foreach ($node['dirs'] as $name => $child) {
            $directories[] = [
                'type' => 'directory',
                'name' => (string)$name,
                'path' => $child['path'],
                'children' => $this->flattenTree($child)
            ];
        }

        $files = $node['files'];

        usort($directories, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return array_merge($directories, $files);
    }

    /**
     * 경로 보안 검증
     *
     * 허용 경로(roots) 안의 FCPATH 기준 상대 경로로 바꿔 돌려준다. 벗어나면 false
     */
    private function getSafePath($path)
    {
        $path = trim(str_replace('\\', '/', (string)$path), '/');

        if ($path === '' || strpos($path, "\0") !== false || preg_match('#(^|/)\.\.?(/|$)#', $path)) {
            return false;
        }

        foreach ($this->roots as $root) {
            $root = trim($root, '/');
            if ($path === $root || strpos($path, $root . '/') === 0) {
                return $path;
            }
        }

        return false;
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
     *
     * S3는 버킷 버전 관리가 이전 내용을 남기므로 따로 백업하지 않는다
     */
    private function backupFile($targetPath)
    {
        if ($this->storage->isS3() || !is_file(FCPATH . $targetPath)) {
            return true;
        }

        $backupBasePath = FCPATH . 'file_backups/' . date('Ymd') . '/';

        if (!is_dir($backupBasePath)) {
            if (!mkdir($backupBasePath, 0755, true)) {
                return false;
            }
        }

        $safeName = str_replace(['/', '\\'], '__', $targetPath);

        $backupFile = $backupBasePath . $safeName . '.' . date('His') . '.bak';

        return copy(FCPATH . $targetPath, $backupFile);
    }
}
