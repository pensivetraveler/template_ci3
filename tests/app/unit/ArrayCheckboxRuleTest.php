<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'helpers/builder/builder_form_helper.php';

/**
 * 다중 체크박스(name[])로 배열이 온 칸만 규칙 이름에 [] 를 붙인다 (CI3 는 [] 없는 칸의 배열을 빈 값으로 본다)
 */
class ArrayCheckboxRuleTest extends TestCase
{
    public function testOnlyCheckboxWithArrayValueGetsBrackets(): void
    {
        $config = [
            ['field' => 'sectors', 'type' => 'checkbox', 'rules' => 'required'],
            ['field' => 'agree_yn', 'type' => 'checkbox', 'rules' => 'required'],
            ['field' => 'company_ids', 'type' => 'select', 'rules' => 'required|max_length[255]'],
            ['field' => 'tags[]', 'type' => 'checkbox', 'rules' => 'required'],
            ['field' => 'missing', 'type' => 'checkbox', 'rules' => 'required'],
        ];
        $data = ['sectors' => ['A', 'B'], 'agree_yn' => 'Y', 'company_ids' => ['1', '2'], 'tags' => ['x']];

        $this->assertSame(
            ['sectors[]', 'agree_yn', 'company_ids', 'tags[]', 'missing'],
            array_column(set_array_checkbox_rule_fields($config, $data), 'field')
        );
    }
}
