<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 엑셀 라이브러리 선택 (.env EXCEL_ENGINE). phpspreadsheet, phpexcel, 비우면 PhpSpreadsheet 가 설치되어 있으면 그것을, 없으면 PHPExcel 을 쓴다.
 * 두 라이브러리는 시트·셀 메서드와 아래 상수 값이 같다. 다른 것은 객체 생성, 읽기·쓰기, 스타일 배열 키뿐이라 여기서만 나눈다.
 */
class Excel_lib
{
    const BORDER_THIN = 'thin';
    const FILL_SOLID = 'solid';
    const ALIGN_CENTER = 'center';
    const COLOR_RED = 'FFFF0000';
    const FORMAT_DATE = 'yyyy-mm-dd';
    const TYPE_STRING = 's';
    const TYPE_NUMERIC = 'n';

    private string $engine;
    private string $today;
    private array $worksheetArr;
    private array $defaultBorder;
    private array $headBorder;
    private $objPHPExcel;
    private $objPHPReader;
    private string $PHPExcelFileName;

    function __construct()
    {
        log_message('Debug', 'Excel Library is loaded.');

        $engine = strtolower((string)getenv('EXCEL_ENGINE'));
        if($engine !== 'phpexcel' && $engine !== 'phpspreadsheet') {
            $engine = class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? 'phpspreadsheet' : 'phpexcel';
        }
        $this->engine = $engine;
        if($engine === 'phpexcel') {
            require_once APPPATH . 'third_party/PHPExcel/PHPExcel.php';
            require_once APPPATH . 'third_party/PHPExcel/PHPExcel/IOFactory.php';
        }

        $this->today = date("Y-m-d");
        $this->worksheetArr = array();
        $this->defaultBorder = array(
            'borderStyle' => self::BORDER_THIN,
            'color' => array('rgb' => 'A6A6A6')
        );
        $this->headBorder = array(
            'borders' => array(
                'bottom' => $this->defaultBorder,
                'left' => $this->defaultBorder,
                'top' => $this->defaultBorder,
                'right' => $this->defaultBorder
            )
        );
        $this->objPHPExcel = null;
        $this->objPHPReader = null;
        $this->PHPExcelFileName = '';
    }

    function load()
    {
        log_message('Debug', "Excel engine {$this->engine} is loaded newly.");
        return $this->objPHPExcel = $this->engine === 'phpexcel' ? new PHPExcel() : new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    /**
     * 스타일 배열을 적용한다. 키는 PhpSpreadsheet 이름으로 쓰고 PHPExcel 이면 옛 이름으로 바꾼다
     */
    public function applyStyle($style, array $values): void
    {
        $style->applyFromArray($this->engine === 'phpexcel' ? $this->legacyStyleKeys($values) : $values);
    }

    private function legacyStyleKeys(array $values): array
    {
        $keys = [
            'allBorders' => 'allborders',
            'borderStyle' => 'style',
            'fillType' => 'type',
            'startColor' => 'startcolor',
            'endColor' => 'endcolor',
            'numberFormat' => 'numberformat',
            'formatCode' => 'code',
        ];
        $result = [];
        foreach ($values as $key => $value) {
            $result[$keys[$key] ?? $key] = is_array($value) ? $this->legacyStyleKeys($value) : $value;
        }
        return $result;
    }

    /**
     * xlsx 로 저장한다 (경로 또는 php://output)
     */
    public function saveXlsx($spreadsheet, string $path): void
    {
        $writer = $this->engine === 'phpexcel'
            ? PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007')
            : \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
    }

    public function readExcel($filename, $startRow = 1, $startCol = 0)
    {
        try {
            // 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
            $objReader = $this->engine === 'phpexcel'
                ? PHPExcel_IOFactory::createReaderForFile($filename)
                : \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filename);
            // 읽기전용으로 설정
            $objReader->setReadDataOnly(true);
            // 엑셀파일을 읽는다
            $objExcel = $objReader->load($filename);
            // 첫번째 시트를 선택
            $objExcel->setActiveSheetIndex(0);
            $objWorksheet = $objExcel->getActiveSheet();
            $rowIterator = $objWorksheet->getRowIterator();

            foreach ($rowIterator as $row) { // 모든 행에 대해서
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
            }

            $maxColumn = alphabet_to_number($objWorksheet->getHighestDataColumn());
            $maxRow = $objWorksheet->getHighestRow();

            $list = [];
            for ($i = $startRow ; $i <= $maxRow ; $i++) {
                $data = [];
                for($j = $startCol; $j <= $maxColumn; $j++) {
                    $colAlphabet = number_to_alphabet($j);
                    $data[] = $objWorksheet->getCell($colAlphabet . $i)->getValue();
                }
                $list[] = $data;
            }
            return $list;
        } catch (exception $e) {
            return false;
        }
    }

    public function setSheet($title, $description)
    {
        $this->objPHPExcel->getProperties()->setCreator(PLATFORM_NAME_KR)
            ->setLastModifiedBy(PLATFORM_NAME_KR)
            ->setTitle(OPERATOR_KR . " " . PLATFORM_NAME_KR)
            ->setSubject($title)
            ->setDescription($description)
            ->setKeywords(OPERATOR_KR . "," . PLATFORM_NAME_KR . ",{$title}")
            ->setCategory(OPERATOR_KR);
        $this->objPHPExcel->getDefaultStyle()->getFont()->setName('Noto Sans');
        $this->objPHPExcel->getDefaultStyle()->getFont()->setSize(12);
        $this->objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(self::ALIGN_CENTER);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->objPHPExcel->setActiveSheetIndex(0);

        // 워크시트 이름 지정
        $this->objPHPExcel->getActiveSheet()->setTitle($title);
    }

    public function setTableHead($data, $excel_key_list)
    {
        $key_arr = array();
        foreach ($data[0] as $key => $val) {
            $key_arr[] = $key;
        }

        foreach ($key_arr as $key_idx => $key_val) {
            $alphabet = number_to_alphabet($key_idx);
            if (array_key_exists($key_val, $excel_key_list)) {
                $cellValue = $excel_key_list[$key_val];
            } else {
                $cellValue = translate_key($key_val);
            }
            $this->objPHPExcel->setActiveSheetIndex(0)->setCellValue("{$alphabet}1", $cellValue);
        }
    }

    public function setTableBody($data)
    {
        foreach ($data as $data_idx => $data_val) {
            $alphabet_idx = 0;
            foreach ($data_val as $row_val) {
                $row_idx = (string)$data_idx + 2;
                $alphabet = number_to_alphabet($alphabet_idx);
                $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue("{$alphabet}{$row_idx}", $row_val);
                $alphabet_idx++;
            }
        }
    }

    public function setFileName($filename)
    {
        $this->PHPExcelFileName = $filename;
    }

    public function download()
    {
        $filename = "{$this->PHPExcelFileName}.xlsx";    // 엑셀 파일명
        $filename = str_replace(' ', '_', $filename);

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');     // 브라우저에서 받을 파일명
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');        // no cache
        //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        // xlsx 로 저장
        // 서버에 파일을 쓰지 않고 바로 다운로드
        $this->saveXlsx($this->objPHPExcel, 'php://output');
    }
}