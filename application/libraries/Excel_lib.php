<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Include PHP Excel library files
require_once APPPATH . '/third_party/PHPExcel/PHPExcel.php';
require_once APPPATH . '/third_party/PHPExcel/PHPExcel/IOFactory.php';

class Excel_lib extends PHPExcel
{
    private string $today;
    private array $worksheetArr;
    private array $defaultBorder;
    private array $headBorder;
    private $objPHPExcel;
    private $objPHPReader;
    private string $PHPExcelFileName;

    function __construct()
    {
        parent::__construct();
        log_message('Debug', 'Excel Library is loaded.');

        $this->today = date("Y-m-d");
        $this->worksheetArr = array();
        $this->defaultBorder = array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
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
        log_message('Debug', 'Third Party PHPExcel is loaded newly.');
        return $this->objPHPExcel = new PHPExcel();
    }

    public function readExcel($filename, $startRow = 1, $startCol = 0)
    {
        try {
            // 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
            $objReader = PHPExcel_IOFactory::createReaderForFile($filename);
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
        $this->objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

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

        // Excel 2007 포맷으로 저장
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');

        // 서버에 파일을 쓰지 않고 바로 다운로드
        $objWriter->save('php://output');
    }
}