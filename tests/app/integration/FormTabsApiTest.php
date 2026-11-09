<?php
/**
 * 폼 탭 정의(tabs_{엔티티}_config)에서 이 사용자에게 숨긴 탭의 값은 서버가 받지 않는다
 */
class FormTabsApiTest extends ApiTestCase
{
    private string $dir = APPPATH.'config/extra/admin/resources';
    private bool $madeDir = false;

    protected function setUp(): void
    {
        parent::setUp();
        // 통합형 리소스 파일로 업체(company) 설정에 탭 정의만 넣는다. 모든 필드가 첫 탭(tab_index 0)이고, 그 탭은 시스템 관리자만 본다
        if (!is_dir($this->dir)) $this->madeDir = mkdir($this->dir);
        file_put_contents($this->dir.'/company.php', "<?php\nreturn ['tabs' => ['main' => ['label' => 'Main', 'user_cd' => ['USR000']]]];\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/company.php');
        if ($this->madeDir) @rmdir($this->dir);
        parent::tearDown();
    }

    public function testHiddenTabValuesAreNotSaved(): void
    {
        self::grant('USR001', 'companies');
        [$adminId] = self::makeUser('USR001');
        $admin = $this->login($adminId);

        $code = str_pad((string)(getmypid() % 900000 + 100000), 6, '0');
        TestDb::exec("DELETE FROM tbl_company WHERE company_code = '$code'");
        TestDb::exec("INSERT INTO tbl_company (company_name, company_cd, company_code, created_id) VALUES ('원래', 'COM001', '$code', 1)");
        $id = (int)TestDb::conn()->insert_id;
        $name = function () use ($id) {
            return TestDb::value("SELECT company_name FROM tbl_company WHERE company_id = $id");
        };

        $response = $admin->request('PATCH', "/admin/api/companies/$id", ['company_name' => '바꿈']);
        $this->assertSame(200, $response->status, $response->body);
        $this->assertSame('원래', $name(), '숨긴 탭의 값은 저장하지 않는다');

        $response = $this->systemAdmin()->request('PATCH', "/admin/api/companies/$id", ['company_name' => '바꿈']);
        $this->assertSame(200, $response->status, $response->body);
        $this->assertSame('바꿈', $name(), '탭을 보는 사용자는 저장된다');
    }
}
