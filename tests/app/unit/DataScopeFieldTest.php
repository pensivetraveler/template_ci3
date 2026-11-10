<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'traits/BuilderApiDataScopeTrait.php';

/**
 * 범위 필드가 Model_Parent(join 한 부모 표) 컬럼이면 조회 조건은 '{부모 표}.col' (koreadesk #koreadesk-2: 'user_cd' 가 메인 표에 붙어 1054)
 */
class DataScopeFieldTest extends TestCase
{
    private function api(array $scope, bool $withParent = true)
    {
        $model = function (string $table, array $columns) {
            return new class($table, $columns) {
                public string $table;
                private array $columns;
                public function __construct($table, $columns) { $this->table = $table; $this->columns = $columns; }
                public function getRequestColumnList(): array { return $this->columns; }
            };
        };
        $api = new class($scope) {
            use BuilderApiDataScopeTrait;
            private array $scope;
            public function __construct($scope) { $this->scope = $scope; }
            protected function getDataScope(): array { return $this->scope; }
            public function run(array $data): array { return $this->applyDataScope($data); }
        };
        $api->Model = $model('administrator', ['admin_id', 'user_id', 'company_id']);
        if ($withParent) $api->Model_Parent = $model('user', ['user_id', 'user_cd', 'company_id']);
        return $api;
    }

    public function testParentColumnUsesParentTable(): void
    {
        $data = $this->api(['user_cd' => ['USR001'], 'company_id' => ['3']])->run([]);
        // 메인·부모 둘 다 있는 컬럼은 메인 표 그대로
        $this->assertSame(['user.user_cd' => ['USR001'], 'company_id' => ['3']], $data['whereIn']);
    }

    public function testIntersectsExistingConditionOnSameKey(): void
    {
        $data = $this->api(['user_cd' => ['USR001', 'USR002']])->run(['whereIn' => ['user.user_cd' => 'USR002,USR000']]);
        $this->assertSame(['USR002'], $data['whereIn']['user.user_cd']);

        // 두 번 붙여도 같다 (MY_Builder_API::list() 는 beforeList 앞뒤로 붙인다)
        $api = $this->api(['user_cd' => ['USR001']]);
        $this->assertSame($api->run([]), $api->run($api->run([])));
    }

    public function testWithoutParentKeepsField(): void
    {
        $this->assertSame(['user_cd' => ['USR001']], $this->api(['user_cd' => ['USR001']], false)->run([])['whereIn']);
    }
}
