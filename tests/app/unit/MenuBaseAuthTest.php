<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'helpers/builder/builder_menu_helper.php';

/**
 * 메뉴 기본 권한(base_auth): list 가 아닌 화면도 exports 를 직접 적으면 E 자리를 켠다 (bizsoft #bizsoft-12: 트래픽 로그 다운로드)
 */
class MenuBaseAuthTest extends TestCase
{
    public function testNonListScreenGetsExportOnlyWhenExportsSet(): void
    {
        $this->assertSame('000010', make_base_auth_from_method(['category' => 'view', 'exports' => ['excel' => true]]));
        $this->assertSame('000000', make_base_auth_from_method(['category' => 'view']));
        $this->assertSame('000000', make_base_auth_from_method(['category' => 'view', 'exports' => ['excel' => false]]));
    }

    public function testListScreenUnchanged(): void
    {
        $list = [
            'category' => 'list',
            'buttons' => ['add' => true],
            'actions' => ['view' => true, 'edit' => false, 'delete' => true],
            'exports' => ['csv' => true],
        ];
        $this->assertSame('110110', make_base_auth_from_method($list));
        unset($list['exports']);
        $this->assertSame('110100', make_base_auth_from_method($list));
    }
}
