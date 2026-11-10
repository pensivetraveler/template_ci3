<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 목록 내보내기: 엑셀·CSV 파일 만들기와 내려받기 (MY_Builder_API)
 */
trait BuilderApiExportTrait
{
    protected function getExportsConfig(): array
    {
        $className = builder_config_name($this->router->class);
        if($this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('form', $className), [], false)) {
            $columns = $this->setFormColumns($className);
        }else{
            $config = $this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('list', $className), [], false);
            $columns = array_reduce($config, function($carry, $item) {
                if(isset($item['field']) || $item['type'] === 'common') {
                    $carry[] = $this->setFormColumn($item);
                }
                return $carry;
            }, []);
        }

        if(empty($columns)) {
            return [];
        }

        // 비밀번호·API 키 등은 내보내지 않는다
        $hidden = array_merge($this->hiddenResponseFields, $this->hiddenListFields);
        // 조회 결과에 있는 컬럼(Model·부모·자식 모델)만 내보낸다. form 의 다른 테이블 필드(스타일 등)는 행에 값이 없다
        $modelColumns = [];
        foreach ($this->getModelPipeline() as $modelName) {
            if(property_exists($this, $modelName) && method_exists($this->{$modelName}, 'getRequestColumnList')) {
                $modelColumns = array_merge($modelColumns, $this->{$modelName}->getRequestColumnList());
            }
        }
        $listColumns = array_merge(array_column($this->listConfig ?: [], 'field'), [RECENT_DT_COLUMN_NAME]);
        return array_values(array_filter($columns, function ($item) use ($hidden, $modelColumns, $listColumns) {
            // 목록 설정에 있는 열(recent_dt 처럼 select 에서 계산하는 값 포함)은 내보낸다
            if(count($modelColumns) && !in_array($item['field'] ?? '', $modelColumns, true) && !in_array($item['field'] ?? '', $listColumns, true)) return false;
            return !(
                $item['type'] === 'common' ||
                $item['type'] === 'file' ||
                $item['type'] === 'password' ||
                $item['subtype'] === 'identifier' ||
                in_array($item['field'] ?? '', $hidden, true)
            );
        }));
    }

    protected function getExportsHeader($config): array
    {
        $header['num'] = 'No.';
        foreach ($config as $item) $header[$item['field']] = lang($item['label']);
        return $header;
    }

    protected function getExportsDataTypes($config): array
    {
        // num formats
        $numFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'int';
        })));

        // num formats
        $floatFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'float';
        })));

        // dateFormats
        $dateFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'date';
        })));

        // optionFormats
        $optionFiltered = array_filter($config, function ($item) {
            return !empty($item['option_attributes']);
        });
        $optionConfigs = array_combine(
            array_column($optionFiltered, 'field'),
            array_column($optionFiltered, 'option_attributes'),
        );
        $optionFields = array_keys($optionConfigs);

        $checkboxFormats = array_column(array_filter($optionFiltered, function ($item) {
            return $item['type'] === 'checkbox';
        }), 'field');

        return [
            'int' => $numFormats,
            'float' => $floatFormats,
            'date' => $dateFormats,
            'option' => [
                'fields' => $optionFields,
                'config' => $optionConfigs,
            ],
            'checkbox' => $checkboxFormats,
        ];
    }

    protected function getExportsDataSource($params): array
    {
        return $this->transformList($this->Model->getList(
            $this->getListSelect($params['select'] ?? [], $this->Model),
            $params,
        ));
    }

    protected function getExportsDataset($data, $header, $formats): array
    {
        // data
        $source = $this->getExportsDataSource($data);

        // dataset
        $i = 0;
        return array_reduce($source, function ($carry, $item) use ($header, $formats, &$i) {
            $result['num'] = ++$i;

            foreach(array_keys($header) as $field) {
                if($field === 'num') continue;

                // form 에는 있지만 조회 결과에 없는 필드(다른 테이블에 저장하는 값 등)는 빈 값
                $value = property_exists($item, $field) ? $item->{$field} : '';
                if(in_array($field, $formats['option']['fields'])) {
                    $optionConfig = $formats['option']['config'][$field];
                    $options = $this->getOptions($field, $optionConfig);
                    if(in_array($field, $formats['checkbox'])) {
                        $seperator = $optionConfig['seperator'] ?? ',';
                        $exploded = explode($seperator, $value);
                        $value = '';
                        // $i 는 행 번호(by-reference)이므로 다른 변수명 사용
                        foreach ($exploded as $idx=>$v) {
                            if(!isset($options[$v])) continue;
                            $value .= $options[$v];
                            if($idx !== count($exploded) -1) $value .= $seperator.' ';
                        }
                    }else{
                        $value = $options[$value] ?? '';
                    }
                }

                $result[$field] = $value;
            }

            $carry[] = $result;
            return $carry;
        }, array());
    }

    protected function addExtraDataset($dataset): array
    {
        return $dataset;
    }

    /**
     * 내보내기 임시 파일 위치. 웹에서 직접 열 수 없는 application/cache/exports/ (downloadExports_get 으로만 내려받음)
     */
    protected function getExportsDir(): string
    {
        return APPPATH.'cache'.DIRECTORY_SEPARATOR.'exports'.DIRECTORY_SEPARATOR;
    }

    protected function prepareExportsPath($exportType): string
    {
        $dir = $this->getExportsDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new Exception('Unable to create exports directory', CREATE_FOLDER_FAIL);
        // 내려받지 않고 남은 파일은 하루 뒤 정리
        foreach (glob($dir.'*') ?: [] as $old) {
            if(is_file($old) && filemtime($old) < time() - 86400) @unlink($old);
        }
        $filename = APP_NAME.'_'.strtolower($this->router->class).'_'.date('YmdHis').'.'.$exportType;
        $encrypted = strtr($this->encryption->encrypt($filename), [
            '+' => '-',
            '/' => '_',
        ]);
        return $dir . $encrypted;
    }

    public function prepareExports_get(): void
    {
        /**
         * 1.Model check
         */
        if(!property_exists($this, 'Model') || is_null($this->Model)) {
            $this->response([
                'code' => MODEL_IS_NOT_DEFINED,
            ]);
        }

        /**
         * 2.Count check
         */
        $data = $this->input->get();
        $exportType = $data['exportType'];
        if(!in_array($exportType, ['csv', 'xlsx'])) {
            $this->response([
                'code' => BAD_REQUEST,
            ]);
        }
        unset($data['exportType']);

        $data = $this->applyDataScope($this->sanitizeRequestConditions(reformat_get_data($data, $this->exceptValidateKeys)));
//        if($this->Model->getCnt($data) === 0) {
//            $this->response([
//                'code' => EMPTY_CONTENT,
//            ]);
//        }

        /**
         * 3.Prepare Data
         */
        $config = $this->getExportsConfig();

        // heads
        $header = $this->getExportsHeader($config);

        // types
        $formats = $this->getExportsDataTypes($config);

        // dataset
        $dataset = $this->getExportsDataset($data, $header, $formats);

        // extra
        $dataset = $this->addExtraDataset($dataset);

        /**
         * 4.Prepare Folder
         */
        try {
            // 저장할 폴더 경로 설정
            $filePath = $this->prepareExportsPath($exportType);

            if(in_array($exportType, ['xls', 'xlsx'])) {
                $this->prepareExcel($header, $dataset, $filePath, $formats);
            }else {
                $this->prepareCSV($header, $dataset, $filePath);
            }

            $this->response([
                'code' => FILE_CREATED,
                'data' => [
                    'filename' => basename($filePath),
                ]
            ]);
        } catch (Exception $e) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
            ]);
        }
    }

    protected function setExcelSheetStyle($sheet, $maxAlphabet, $rowMax): void
    {
        $range = get_alphabet_range('B', $maxAlphabet);
        foreach ($range as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(20);
        }
        $this->excel_lib->applyStyle(
            $sheet->getStyle('A1:'.$maxAlphabet.$rowMax),
            array(
                'alignment' => array(
                    'vertical' => Excel_lib::ALIGN_CENTER,
                ),
                'borders' => array(
                    'allBorders' => array(
                        'borderStyle' => Excel_lib::BORDER_THIN,
                    ),
                ),
            )
        );
    }

    protected function prepareExcel($header, $dataset, $filepath, $formats = []): void
    {
        $this->load->library('excel_lib');
        $this->load->helper('excel');
        $objPHPExcel = $this->excel_lib->load();

        // sheet
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle(date('Y-m-d'));
        $objPHPExcel->setActiveSheetIndex(0);

        // head
        $maxAlphabet = number_to_alphabet(count(array_keys($header))-1);
        foreach (array_keys($header) as $i=>$key) {
            $coord = number_to_alphabet($i);
            $value = $header[$key];
            $sheet
                ->setCellValue($coord.'1',$value);
        }

        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFont()->setBold(true);
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFill()->setFillType(Excel_lib::FILL_SOLID);
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFill()->getStartColor()->setRGB('EEEEEE');
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getAlignment()->setHorizontal(Excel_lib::ALIGN_CENTER);

        // body
        foreach ($dataset as $i=>$item) {
            $j = 0;
            foreach ($item as $field=>$value) {
                $coord = number_to_alphabet($j);
                $this->setExcelValue($sheet, $coord.($i+2), $field, $value, $formats);
                $j++;
            }
        }

        $rowMax = strval(count($dataset)+1);

        $this->setExcelSheetStyle($sheet, $maxAlphabet, $rowMax);

        // 파일 저장
        error_clear_last(); // 이전 에러 초기화

        try {
            $this->excel_lib->saveXlsx($objPHPExcel, $filepath);
        } catch (Exception $e) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
                'msg' => $e->getMessage()
            ]);
        }

        $last_error = error_get_last();
        if ($last_error) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
                'msg' => $last_error['message']
            ]);
        }
    }

    protected function setExcelValue($sheet, $location, $field, $value, $formats)
    {
        if(in_array($field, $formats['date'])){
            if(!$value) {
                $sheet->setCellValue($location, '');
            }else{
                $diffInSeconds = strtotime($value) - strtotime('1899-12-30');
                $diffInDays = floor($diffInSeconds / (60 * 60 * 24))+1;
                $sheet->setCellValue($location, $diffInDays);
                $sheet->getStyle($location)
                    ->getNumberFormat()
                    ->setFormatCode(Excel_lib::FORMAT_DATE);
            }
        }elseif(
            in_array($field, $formats['int'])
            ||
            in_array($field, $formats['float'])
        ) {
            if(!$value) $value = 0;

            // 숫자가 아니면 PhpSpreadsheet 가 예외를 던진다. 값을 잃지 않게 글자로 쓴다
            $sheet->setCellValueExplicit(
                $location,
                $value,
                is_numeric($value) ? Excel_lib::TYPE_NUMERIC : Excel_lib::TYPE_STRING
            );

            if(in_array($field, $formats['float'])) {
                $sheet->getStyle($location)
                    ->getNumberFormat()
                    ->setFormatCode('0.00');
            }
        }else {
            if(!$value) $value = '';

            $sheet->setCellValueExplicit($location, $value, Excel_lib::TYPE_STRING);
        }
    }

    protected function prepareCSV($header, $dataset, $filepath): void
    {
        // 예시 데이터 (보통은 DB에서 가져오겠지)
        array_unshift($dataset, $header);

        // 파일 열기
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            $this->response([
                'code' => PERMISSION_OR_DISK_ERROR,
            ]);
        }

        // UTF-8 BOM (Excel 에서 한글 깨짐 방지)
        fwrite($fp, "\xEF\xBB\xBF");

        // fputcsv 로 한 줄씩 쓰기
        foreach ($dataset as $row) {
            $row = array_values($row);
            fputcsv($fp, $row);  // 기본 구분자: 콤마 (,)
        }

        fclose($fp);
    }

    public function downloadExports_get(): void
    {
        $encrypted = $this->input->get('filename');
        if(empty($encrypted)) $this->response(['code' => EMPTY_REQUIRED_DATA], RestController::HTTP_BAD_REQUEST);

        // 암호화된 파일명만 허용 (경로 조작 방지)
        if(!preg_match('/^[A-Za-z0-9_=-]+$/', (string)$encrypted)) $this->response(['code' => FILE_NOT_EXIST], RestController::HTTP_NOT_FOUND);
        $fullPath = $this->getExportsDir() . $encrypted;
        if(!file_exists($fullPath)) $this->response(['code' => FILE_NOT_EXIST], RestController::HTTP_NOT_FOUND);

        $encrypted = strtr($encrypted, [
            '-' => '+',
            '_' => '/',
        ]);
        $filename = $this->encryption->decrypt($encrypted);
        if($filename === false) $this->response(['code' => WRONG_TOKEN], RestController::HTTP_BAD_REQUEST);

        $fileContents = file_get_contents($fullPath);
        @unlink($fullPath);

        force_download($filename, $fileContents, true);
    }
}
