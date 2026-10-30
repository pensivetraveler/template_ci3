<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 파일 저장소 (로컬 디스크 / S3)
 *
 * 키는 FCPATH 기준 상대 경로를 쓴다. 예) public/templates/sample/config.json
 * - local : FCPATH.키 에 읽고 쓴다 (지금까지와 같음)
 * - s3    : 버킷의 키에서 앞의 'public/'을 뗀 경로에 읽고 쓴다. 예) templates/sample/config.json
 *
 * 환경 변수
 * - STORAGE_DRIVER : local(기본) | s3
 * - STORAGE_BUCKET : S3 버킷 이름
 * - STORAGE_URL    : S3 파일을 내려주는 주소 (CloudFront). 예) https://cdn.example.com
 * - STORAGE_REGION : 버킷 리전 (없으면 AWS_DEFAULT_REGION)
 * - REDIS_HOST, REDIS_PORT : 있으면 S3 읽기 결과를 Redis에 캐시 (phpredis 확장 필요)
 *
 * S3 자격 증명은 AWS SDK 기본 순서(환경 변수 → ~/.aws → EC2 역할)를 따른다.
 */
class Storage
{
    const CACHE_PREFIX = 'storage:';
    const CACHE_TTL = 300;
    const CACHE_MAX_BYTES = 524288;

    protected $driver;
    protected $bucket = '';
    protected $baseUrl = '';
    protected $client = null;
    protected $redis = null;

    public function __construct()
    {
        $this->driver = getenv('STORAGE_DRIVER') === 's3' ? 's3' : 'local';

        if ($this->isS3()) {
            $this->bucket = (string)getenv('STORAGE_BUCKET');
            $this->baseUrl = rtrim((string)getenv('STORAGE_URL'), '/');
            if ($this->bucket === '') show_error('STORAGE_BUCKET is not set');

            $this->client = new Aws\S3\S3Client([
                'version' => '2006-03-01',
                'region' => getenv('STORAGE_REGION') ?: (getenv('AWS_DEFAULT_REGION') ?: 'ap-northeast-1'),
            ]);
            $this->connectRedis();
        }
    }

    public function isS3(): bool
    {
        return $this->driver === 's3';
    }

    /* ------------------------------------------------------------------
     * 읽기
     * ------------------------------------------------------------------ */

    public function exists($key): bool
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) return is_file(FCPATH . $key);

        return $this->version($key) !== null;
    }

    /**
     * 디렉터리(접두사) 아래에 파일이 하나라도 있는지
     */
    public function isDir($prefix): bool
    {
        $prefix = $this->normalize($prefix);
        if (!$this->isS3()) return is_dir(FCPATH . $prefix);

        try {
            $result = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $this->s3Key($prefix) . '/',
                'MaxKeys' => 1,
            ]);
            return (int)$result['KeyCount'] > 0;
        } catch (Exception $e) {
            log_message('error', 'Storage::isDir ' . $prefix . ' : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 파일 내용. 없으면 null
     */
    public function read($key): ?string
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) {
            if (!is_file(FCPATH . $key)) return null;
            $content = file_get_contents(FCPATH . $key);
            return $content === false ? null : $content;
        }

        $cached = $this->cacheGet('body:' . $key);
        if ($cached !== null) return $cached;

        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->s3Key($key),
            ]);
            $content = (string)$result['Body'];
            if (strlen($content) <= self::CACHE_MAX_BYTES) {
                $this->cacheSet('body:' . $key, $content);
                $this->cacheSet('ver:' . $key, trim((string)$result['ETag'], '"'));
            }
            return $content;
        } catch (Aws\S3\Exception\S3Exception $e) {
            if ($e->getStatusCode() !== 404) log_message('error', 'Storage::read ' . $key . ' : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 파일 내용이 바뀌면 달라지는 값 (정적 파일 ?v= 용). 없으면 null
     */
    public function version($key): ?string
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) return is_file(FCPATH . $key) ? md5_file(FCPATH . $key) : null;

        $cached = $this->cacheGet('ver:' . $key);
        if ($cached !== null) return $cached;

        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $this->s3Key($key),
            ]);
            $etag = trim((string)$result['ETag'], '"');
            $this->cacheSet('ver:' . $key, $etag);
            return $etag;
        } catch (Aws\S3\Exception\S3Exception $e) {
            if ($e->getStatusCode() !== 404) log_message('error', 'Storage::version ' . $key . ' : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 접두사 아래 모든 파일 키 (하위 폴더 포함, 숨김 파일 제외)
     */
    public function listFiles($prefix): array
    {
        $prefix = $this->normalize($prefix);
        $keys = [];

        if (!$this->isS3()) {
            $dir = FCPATH . $prefix;
            if (!is_dir($dir)) return [];

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                $key = $prefix . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
                if ($this->isHidden($key)) continue;
                $keys[] = $key;
            }
            sort($keys);
            return $keys;
        }

        try {
            $pages = $this->client->getPaginator('ListObjectsV2', [
                'Bucket' => $this->bucket,
                'Prefix' => $this->s3Key($prefix) . '/',
            ]);
            foreach ($pages as $page) {
                foreach ($page['Contents'] ?? [] as $object) {
                    $key = $this->fromS3Key($object['Key']);
                    if (substr($key, -1) === '/' || $this->isHidden($key)) continue;
                    $keys[] = $key;
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Storage::listFiles ' . $prefix . ' : ' . $e->getMessage());
        }
        sort($keys);
        return $keys;
    }

    /**
     * 공개 주소
     */
    public function url($key): string
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) return base_url($key);

        $encoded = implode('/', array_map('rawurlencode', explode('/', $this->s3Key($key))));
        return $this->baseUrl . '/' . $encoded;
    }

    /* ------------------------------------------------------------------
     * 쓰기
     * ------------------------------------------------------------------ */

    /**
     * 내용 저장. 성공하면 바이트 수, 실패하면 false
     */
    public function write($key, string $content)
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) {
            $dir = dirname(FCPATH . $key);
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
            return file_put_contents(FCPATH . $key, $content, LOCK_EX);
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->s3Key($key),
                'Body' => $content,
                'ContentType' => $this->contentType($key),
                'CacheControl' => $this->cacheControl($key),
            ]);
            $this->forget($key);
            return strlen($content);
        } catch (Exception $e) {
            log_message('error', 'Storage::write ' . $key . ' : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 로컬 파일을 저장소에 올림 (업로드 파일 등)
     */
    public function putFile($key, $localPath): bool
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) {
            $target = FCPATH . $key;
            if (realpath($localPath) === realpath($target)) return true;
            $dir = dirname($target);
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
            return copy($localPath, $target);
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->s3Key($key),
                'SourceFile' => $localPath,
                'ContentType' => $this->contentType($key),
                'CacheControl' => $this->cacheControl($key),
            ]);
            $this->forget($key);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Storage::putFile ' . $key . ' : ' . $e->getMessage());
            return false;
        }
    }

    public function delete($key): bool
    {
        $key = $this->normalize($key);
        if (!$this->isS3()) return is_file(FCPATH . $key) && unlink(FCPATH . $key);

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->s3Key($key),
            ]);
            $this->forget($key);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Storage::delete ' . $key . ' : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 폴더 통째로 복사 (테마 → 템플릿 등)
     */
    public function copyDir($srcPrefix, $dstPrefix): bool
    {
        $srcPrefix = $this->normalize($srcPrefix);
        $dstPrefix = $this->normalize($dstPrefix);
        if (!$this->isS3()) {
            get_instance()->load->helper('directory');
            return copy_directory(FCPATH . $srcPrefix, FCPATH . $dstPrefix, 0755);
        }

        $keys = $this->listFiles($srcPrefix);
        if (empty($keys)) return false;

        try {
            foreach ($keys as $key) {
                $dst = $dstPrefix . substr($key, strlen($srcPrefix));
                $this->client->copyObject([
                    'Bucket' => $this->bucket,
                    'Key' => $this->s3Key($dst),
                    'CopySource' => $this->bucket . '/' . implode('/', array_map('rawurlencode', explode('/', $this->s3Key($key)))),
                ]);
                $this->forget($dst);
            }
            return true;
        } catch (Exception $e) {
            log_message('error', 'Storage::copyDir ' . $srcPrefix . ' → ' . $dstPrefix . ' : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 폴더 통째로 삭제. S3는 버킷 버전 관리로 이전 버전이 남는다
     */
    public function deleteDir($prefix): bool
    {
        $prefix = $this->normalize($prefix);
        if ($prefix === '' || $prefix === 'public') return false;

        if (!$this->isS3()) {
            get_instance()->load->helper('directory');
            return !is_dir(FCPATH . $prefix) || delete_directory(FCPATH . $prefix);
        }

        $keys = $this->listFiles($prefix);
        try {
            foreach (array_chunk($keys, 1000) as $chunk) {
                $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Delete' => [
                        'Objects' => array_map(function ($key) {
                            return ['Key' => $this->s3Key($key)];
                        }, $chunk),
                        'Quiet' => true,
                    ],
                ]);
                foreach ($chunk as $key) $this->forget($key);
            }
            return true;
        } catch (Exception $e) {
            log_message('error', 'Storage::deleteDir ' . $prefix . ' : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 폴더를 로컬 디렉터리로 내려받음 (ZIP 만들기 등)
     */
    public function downloadDir($prefix, $localDir): bool
    {
        $prefix = $this->normalize($prefix);
        $localDir = rtrim($localDir, '/\\');

        foreach ($this->listFiles($prefix) as $key) {
            $target = $localDir . '/' . substr($key, strlen($prefix) + 1);
            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) return false;

            if ($this->isS3()) {
                try {
                    $this->client->getObject([
                        'Bucket' => $this->bucket,
                        'Key' => $this->s3Key($key),
                        'SaveAs' => $target,
                    ]);
                } catch (Exception $e) {
                    log_message('error', 'Storage::downloadDir ' . $key . ' : ' . $e->getMessage());
                    return false;
                }
            } elseif (!copy(FCPATH . $key, $target)) {
                return false;
            }
        }
        return true;
    }

    /* ------------------------------------------------------------------
     * file 테이블의 full_path 값 (로컬 절대 경로 / s3://버킷/키)
     * ------------------------------------------------------------------ */

    public function uri($key): string
    {
        $key = $this->normalize($key);
        return $this->isS3() ? 's3://' . $this->bucket . '/' . $this->s3Key($key) : FCPATH . $key;
    }

    public function keyFromUri($uri): string
    {
        $uri = str_replace('\\', '/', (string)$uri);
        if (strpos($uri, 's3://') === 0) {
            $rest = substr($uri, 5);
            $slash = strpos($rest, '/');
            return $this->fromS3Key($slash === false ? '' : substr($rest, $slash + 1));
        }

        foreach (array_unique([str_replace('\\', '/', FCPATH), str_replace('\\', '/', (string)realpath(FCPATH)) . '/']) as $base) {
            if ($base !== '/' && strpos($uri, $base) === 0) return substr($uri, strlen($base));
        }
        // 다른 설치 경로에서 저장한 절대 경로(서버 이전 등)는 업로드 폴더부터
        $uploads = strpos($uri, '/public/uploads/');
        if ($uploads !== false) return substr($uri, $uploads + 1);
        return ltrim($uri, '/');
    }

    /* ------------------------------------------------------------------
     * 내부
     * ------------------------------------------------------------------ */

    protected function normalize($key): string
    {
        $key = str_replace('\\', '/', (string)$key);
        $fcpath = str_replace('\\', '/', FCPATH);
        if (strpos($key, $fcpath) === 0) $key = substr($key, strlen($fcpath));
        $key = trim($key, '/');

        if (strpos($key, "\0") !== false || preg_match('#(^|/)\.\.(/|$)#', $key)) {
            throw new InvalidArgumentException('Invalid storage key: ' . $key);
        }
        return preg_replace('#/+#', '/', $key);
    }

    protected function s3Key($key): string
    {
        return strpos($key, 'public/') === 0 ? substr($key, 7) : $key;
    }

    protected function fromS3Key($s3Key): string
    {
        return 'public/' . ltrim($s3Key, '/');
    }

    protected function isHidden($key): bool
    {
        foreach (explode('/', $key) as $segment) {
            if ($segment !== '' && $segment[0] === '.') return true;
        }
        return false;
    }

    protected function contentType($key): string
    {
        $types = [
            'html' => 'text/html; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'txt' => 'text/plain; charset=utf-8',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
        ];
        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        if (isset($types[$ext])) return $types[$ext];

        get_instance()->load->helper('file');
        return get_mime_by_extension($key) ?: 'application/octet-stream';
    }

    /**
     * 브라우저·CDN 캐시 (루트 .htaccess와 같은 기준)
     * - css/js/html/json: 매번 변경 여부 확인 (?v= 가 붙은 주소는 CloudFront 캐시 키가 달라짐)
     */
    protected function cacheControl($key): string
    {
        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'])) return 'public, max-age=3600';
        if (in_array($ext, ['woff', 'woff2', 'ttf', 'otf', 'eot'])) return 'public, max-age=2592000';

        return 'no-cache';
    }

    protected function connectRedis()
    {
        $host = getenv('REDIS_HOST');
        if (!$host || !class_exists('Redis')) return;

        try {
            $redis = new Redis();
            if ($redis->connect($host, (int)(getenv('REDIS_PORT') ?: 6379), 1.0)) $this->redis = $redis;
        } catch (Exception $e) {
            log_message('error', 'Storage redis connect : ' . $e->getMessage());
        }
    }

    protected function cacheGet($name): ?string
    {
        if (!$this->redis) return null;
        try {
            $value = $this->redis->get(self::CACHE_PREFIX . $this->bucket . ':' . $name);
            return $value === false ? null : $value;
        } catch (Exception $e) {
            return null;
        }
    }

    protected function cacheSet($name, string $value)
    {
        if (!$this->redis) return;
        try {
            $this->redis->setex(self::CACHE_PREFIX . $this->bucket . ':' . $name, self::CACHE_TTL, $value);
        } catch (Exception $e) {
        }
    }

    protected function forget($key)
    {
        if (!$this->redis) return;
        try {
            $this->redis->del([
                self::CACHE_PREFIX . $this->bucket . ':body:' . $key,
                self::CACHE_PREFIX . $this->bucket . ':ver:' . $key,
            ]);
        } catch (Exception $e) {
        }
    }
}
