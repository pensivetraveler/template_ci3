<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'traits/BuilderApiCrudTrait.php';

/**
 * 꺼진 onoff 를 'N' 으로 채우는 것은 이 모델의 컬럼만 (bizsoft #bizsoft-10: 자식 표 칸까지 채워 등록 때 'Y' 가 'N' 으로 덮였음)
 */
class SetDataOnoffTest extends TestCase
{
    public function testOnlyModelColumnsGetDefaultN(): void
    {
        $model = new class {
            public array $notNullList = ['name', 'devtool_onoff'];
            public array $nullList = [];
            public array $strList = [];
            public array $intList = [];
            public array $multilineList = [];
            public bool $isDelYn = false;
            public bool $isUseYn = false;
        };
        $api = new class {
            use BuilderApiCrudTrait;
            public array $formConfig = [
                ['field' => 'name', 'type' => 'text', 'subtype' => 'base'],
                ['field' => 'devtool_onoff', 'type' => 'checkbox', 'subtype' => 'onoff'],
                ['field' => 'show_refresh_button', 'type' => 'checkbox', 'subtype' => 'onoff'],
            ];
            public function run($data, $model) { return $this->setData($data, $model); }
        };

        $this->assertSame(
            ['name' => '프레임', 'devtool_onoff' => 'N'],
            $api->run(['name' => '프레임', 'show_refresh_button' => 'Y'], $model)
        );
    }
}
