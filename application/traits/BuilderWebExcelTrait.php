<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 엑셀 일괄 등록 화면과 샘플 (MY_Builder_WEB)
 */
trait BuilderWebExcelTrait
{
    public function excel()
    {
        $this->addJS['head'][] = [
            base_url('public/assets/builder/vendor/libs/jquery-tabledit/jquery.tabledit.js'),
            base_url('public/assets/builder/js/app-page-excel.js'),
            "https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js",
        ];

        $this->titleList[] = 'Excel';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['excelHeaders'] = $this->getExcelHeaders();
        $data['sampleFile'] = $this->getExcelSample($data['excelHeaders']);

        if(!count($data['excelHeaders'])) show_error('Please Check The Excel Header List', 500);

        $this->viewApp($data);
    }

    protected function getExcelHeaders()
    {
        // excel 설정이 없으면 아래 else에서 form 컬럼으로 헤더를 만든다
        $config = $this->resourceLoader->entityConfig($this->setConfigList(
            'excel', $this->methodConfig['resourceConfig'] ?? null
        ), [], false);

        if($config) {
            return array_reduce($config, function($carry, $item) {
                if(isset($item['field'])) {
                    if(!array_key_exists('required', $item)) {
                        $item['required'] = false;
                    }
                    if(!array_key_exists('label', $item) || !$item['label']) {
                        $item['label'] = $item['field'];
                    }
                    $item['label'] = lang($item['label']);
                    $carry[] = $item;
                }
                return $carry;
            }, []);
        }else{
            $config = [];
            // excel 화면에서는 formColumns 를 미리 만들지 않으므로 여기서 form 설정을 읽는다 (form_{class}_excel → form_{class} 순)
            $formColumns = $this->formColumns ?: $this->setFormColumns($this->methodConfig['properties']['formConfig'] ?? null);
            foreach ($formColumns as $column) {
                if(!$column['form'] || !isset($column['field']) || in_array($column['type'], ['hidden', 'common', 'file', 'password'], true)) continue;
                if(in_array($column['field'], [CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME, RECENT_DT_COLUMN_NAME])) continue;
                if(preg_match('/matches\[(.*?)\]/', $column['rules'], $matches)) continue;
                $config[] = [
                    'field' => $column['field'],
                    // 등록 기준 필수: required 또는 required_mod[...add...] (서버 validateExcelData 와 같은 기준)
                    'required' => (bool)preg_match('/(^|\|)required(\||$)|required_mod\[[^\]]*add/', $column['rules'] ?? ''),
                    'label' => lang($column['label']??$column['field']),
                ];
            }
            return $config;
        }
    }

    protected function getExcelSample($data): string
    {
        $sampleUri = '';
        $filename = $this->router->class.'_upload_sample.xlsx';
        $filepath = 'public'.DIRECTORY_SEPARATOR.'sample'.DIRECTORY_SEPARATOR;

        if(file_exists(FCPATH.$filepath.$filename) || count($data)) {
            $sampleUri = DIRECTORY_SEPARATOR.$filepath.$filename;

            if(!file_exists(FCPATH.$filepath.$filename) && count($data)) {
                $this->load->library('excel_lib');
                $this->load->helper('excel');
                $excel = $this->excel_lib->load();
                $excel->setActiveSheetIndex(0);
                $sheet = $excel->getActiveSheet();

                for($i = 0; $i < count($data); $i++) {
                    $alphabet = number_to_alphabet($i);
                    $sheet->setCellValue($alphabet.'1', $data[$i]['label']);

                    if($data[$i]['required']) {
                        $sheet->getStyle($alphabet.'1')
                            ->getFont()->setBold(true)
                            ->getColor()->setARGB(Excel_lib::COLOR_RED);
                    }

                    $sheet->getColumnDimension($alphabet)->setWidth(24);
                }
                $lastAlphabet = number_to_alphabet(count($data)-1);

                $this->excel_lib->applyStyle($sheet->getStyle('A1:'.$lastAlphabet.'1'), [
                    'alignment' => [
                        'horizontal' => Excel_lib::ALIGN_CENTER, // 가로 가운데 정렬
                    ],
                    'fill' => [
                        'fillType' => Excel_lib::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                ]);
                $this->excel_lib->applyStyle($sheet->getStyle('A1:'.$lastAlphabet.'5'), [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Excel_lib::BORDER_THIN,
                            'color' => array('rgb' => 'A6A6A6')
                        ],
                    ],
                ]);

                // 폴더가 없으면 생성
                if (!is_dir(FCPATH.$filepath)) make_directory($filepath, 0755);

                $this->excel_lib->saveXlsx($excel, FCPATH.$filepath . $filename);
            }
        }
        return $sampleUri;
    }
}
