<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'helpers/builder/builder_form_helper.php';

class FormTabsTest extends TestCase
{
    private function tabs($userCd): array
    {
        return resolve_form_tabs([
            'home' => ['label' => 'lang:frame.tab_home', 'icon' => 'ri-home-smile-line'],
            'columns' => ['label' => 'Columns', 'user_cd' => ['USR000'], 'hide_empty' => true],
            'features' => ['label' => 'Features', 'user_cd' => 'USR000'],
        ], $userCd);
    }

    public function testTabsKeepOrderAndVisibility(): void
    {
        $tabs = $this->tabs('USR002');
        $this->assertSame(['home', 'columns', 'features'], array_keys($tabs));
        $this->assertTrue($tabs['home']['visible']);
        $this->assertFalse($tabs['columns']['visible']);
        $this->assertFalse($tabs['features']['visible']);
        $this->assertTrue($this->tabs('USR000')['features']['visible']);
        $this->assertSame([], resolve_form_tabs(null, 'USR000'));
    }

    public function testItemTabFromNameIndexOrGroup(): void
    {
        $tabs = $this->tabs('USR000');
        $this->assertSame('features', get_form_item_tab(['field' => 'a', 'tab' => 'features', 'tab_index' => 0], $tabs));
        $this->assertSame('columns', get_form_item_tab(['field' => 'a', 'tab' => '', 'tab_index' => 1], $tabs));
        // 탭 정의보다 큰 번호는 첫 탭
        $this->assertSame('home', get_form_item_tab(['field' => 'a', 'tab_index' => 9], $tabs));
        // 그룹 필드는 group_attributes 의 값
        $this->assertSame('features', get_form_item_tab([
            'field' => 'a', 'tab_index' => 0, 'group' => 'g', 'group_attributes' => ['tab' => 'features'],
        ], $tabs));
        // 탭 정의가 없으면 번호 그대로
        $this->assertSame(2, get_form_item_tab(['field' => 'a', 'tab_index' => 2], []));
    }

    public function testHiddenTabFields(): void
    {
        $config = [
            ['field' => 'name', 'tab' => 'home'],
            ['field' => 'match_columns', 'tab' => 'columns'],
            ['field' => 'feature_a', 'group' => 'features_group', 'group_attributes' => ['tab' => 'features']],
            ['type' => 'common', 'tab' => 'features'],
        ];
        $this->assertSame(['match_columns', 'feature_a', 'features_group'], get_hidden_tab_fields($config, $this->tabs('USR002')));
        $this->assertSame([], get_hidden_tab_fields($config, $this->tabs('USR000')));
    }

    public function testReformatTabsByDefinition(): void
    {
        $fields = [
            ['field' => 'name', 'type' => 'text', 'tab' => 'home', 'colspan' => 12],
            ['field' => 'feature_a', 'type' => 'text', 'tab' => 'features', 'colspan' => 12],
        ];
        $data = reformat_form_data_by_type($fields, 'tabs', 'base', $this->tabs('USR002'));

        // 필드가 없는 hide_empty 탭은 빠지고, 헤더를 숨긴 탭도 내용은 남는다
        $this->assertSame(['home', 'features'], array_column($data['tabs'], 'key'));
        $this->assertFalse($data['tabs'][1]['visible']);
        $this->assertSame('feature_a', $data['fields']['features'][0]['field']);
    }

    public function testReformatTabsWithoutDefinitionUsesIndexes(): void
    {
        $fields = [
            ['field' => 'b', 'type' => 'text', 'tab_index' => 1, 'colspan' => 12],
            ['field' => 'a', 'type' => 'text', 'tab_index' => 0, 'colspan' => 12],
        ];
        $data = reformat_form_data_by_type($fields, 'tabs', 'base');

        $this->assertSame(['0', '1'], array_column($data['tabs'], 'key'));
        $this->assertSame('Tab 2', $data['tabs'][1]['label']);
        $this->assertSame('a', $data['fields'][0][0]['field']);
        $this->assertSame('b', $data['fields'][1][0]['field']);
    }
}
