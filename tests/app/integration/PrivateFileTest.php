<?php
/**
 * 비공개 업로드 다운로드 권한. 파일 행과 파일을 직접 만들어 시험한다 (업로드 설정을 바꾸지 않게).
 */
class PrivateFileTest extends ApiTestCase
{
    private string $dir;
    private string $file;
    private int $fileId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = FCPATH.'application/uploads/articles/test/';
        if (!is_dir($this->dir)) mkdir($this->dir, 0777, true);
        $name = uniqid('f').'.txt';
        $this->file = $this->dir.$name;
        file_put_contents($this->file, 'secret-body');
        $key = 'application/uploads/articles/test/'.$name;
        TestDb::exec("INSERT INTO tbl_file (file_name, file_type, file_link, file_path, full_path, raw_name, orig_name, client_name, file_ext, file_size, is_image, created_id) VALUES ('$name', 'text/plain', '$key', 'application/uploads/articles/test/', '$key', 'f', '$name', '$name', '.txt', 1, 0, 1)");
        $this->fileId = (int)TestDb::conn()->insert_id;
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @rmdir($this->dir);
        @rmdir(dirname($this->dir));
        @rmdir(dirname($this->dir, 2));
    }

    private function download(ApiClient $client, string $class = 'articles'): string
    {
        return $client->request('GET', "/admin/$class/downloader/{$this->fileId}")->body;
    }

    public function testReadersDownloadOthersDoNot(): void
    {
        self::grant('USR001', 'articles');
        TestDb::exec("DELETE FROM tbl_menu_auth WHERE grade_cd = 'USR002'");
        self::grant('USR002', 'articles', '001000');

        [$reader] = self::makeUser('USR001');
        [$noRead] = self::makeUser('USR002');
        $readerClient = $this->login($reader);

        $this->assertSame('secret-body', $this->download($readerClient));
        $this->assertSame('secret-body', $this->download($this->systemAdmin()));
        $this->assertStringNotContainsString('secret-body', $this->download(new ApiClient()), '비로그인');
        $this->assertStringNotContainsString('secret-body', $this->download($this->login($noRead)), '읽기 권한 없음');
        $this->assertStringNotContainsString('secret-body', $this->download($this->systemAdmin(), 'boards'), '다른 화면 주소');
        $this->assertStringNotContainsString('secret-body', $this->download(new ApiClient(), 'auth'), '로그인 전 화면 주소');
    }

    public function testDirectUrlIsBlockedByWebServer(): void
    {
        // php -S 는 .htaccess 를 읽지 않으므로 Apache 로 확인한다
        $key = str_replace(FCPATH, '', $this->file);
        $headers = @get_headers('http://127.0.0.1/'.$key);
        $this->assertNotFalse($headers);
        $this->assertStringContainsString('403', $headers[0]);
    }
}
