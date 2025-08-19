<?php

namespace App\Service\Admin;

use Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * ExcelManager class provide you an API to generate excels easylly from array.
 *
 * Methods:
 *   * __construct
 *   * generateExcel
 *   * setColRange
 *   * getData
 *   * setHeaders
 *   * getHeaders
 *   * setBody
 *   * getBody
 *
 */
class ExcelManager
{
    private $data;

    private $headers = [];
    private $body = [];

    private $firstCol;
    private $lastCol;

    public $headerOptions = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '333333'],
            'size' => 10,
            'name' => 'Verdana'
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => '98c46c']
        ],
        'alinegment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ];

    public $bodyOptions = [
        'font' => [
            'bold' => false,
            'color' => ['rgb' => '000000'],
            'size' => 9,
            'name' => 'Verdana'
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE,
            'startColor' => ['argb' => '00ffffff']
        ],
        'alinegment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ];


    /**
     * @param array $arrayData Array of data that will appear on the excel.
     *
     * @return Exception Returns Exception if $arrayData is an array of objects or if any cell contain an array.
     */
    public function __construct(array $arrayData)
    {
        $this->data = $arrayData;

        foreach ($arrayData as $row) {
            if (is_object($row)) throw new Exception('$arrayData can not be an array of objects');

            foreach ($row as $cell) {
                if (is_array($cell)) throw new Exception('One o more cells contain an array');
            }
        }

        for ($i = 0; $i < count($arrayData); $i++) {
            foreach ($arrayData[$i] as $columnName => $column) {
                if (is_array($column)) {
                    $column = implode(", ", $column);
                }

                $this->headers[] = $columnName;
                $this->body[$i][] = $column;
            }
        }

        $this->headers = array_unique($this->headers);

        $cols = $this->setColRange(count($this->headers));
        $this->firstCol = $cols[0];
        $this->lastCol = $cols[count($cols) - 1];
    }


    /** Return the excel $temp_file path with data from array passed to construct
     *
     * @return String|false Returns excel $temp_file path to download it.
     */
    public function generateExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($this->headers, null, 'A1', true);
        $sheet->fromArray($this->body, null, 'A2', true);

        foreach (range($this->firstCol, $this->lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->getStyle($col . "1")->getAlignment()->setHorizontal('center');
            $sheet->getStyle($col . "1")->applyFromArray($this->headerOptions);
        }

        foreach (range($this->firstCol, $this->lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);

            for ($i = 2; $i <= count($this->body) + 1; $i++) {
                $sheet->getStyle($col . $i)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($col . $i)->applyFromArray($this->bodyOptions);
            }
        }

        $writter = new Xlsx($spreadsheet);

        $temp_file = tempnam(sys_get_temp_dir(), "register.xslx");
        $writter->save($temp_file);

        return $temp_file;
    }


    /** Set all col names depending of the array data passed to construct.
     *
     * @return Array Returns array with all col names.
     */
    public function setColRange(int $colNumber)
    {
        $alphabetArray = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];

        $cols = [];

        $loops = ($colNumber / count($alphabetArray) <= 1) ? 1 : ceil($colNumber / count($alphabetArray));
        $lastLoopLettersNumber = ($colNumber % count($alphabetArray) == 0) ? count($alphabetArray) : $colNumber % count($alphabetArray);

        $concat = "";

        for ($i = 0; $i < $loops - 1; $i++) {
            if ($i > 0) $concat = $alphabetArray[$i - 1];

            for ($j = 0; $j < count($alphabetArray); $j++) {
                $cols[] = $concat . $alphabetArray[$j];
            }
        }

        for ($i = 0; $i < $lastLoopLettersNumber; $i++) {
            $cols[] = $concat . $alphabetArray[$i];
        }

        return $cols;
    }


    /**
     * Get the value of data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of headers
     */
    public function setHeaders($headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get the value of headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set the value of body
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get the value of body
     */
    public function getBody()
    {
        return $this->body;
    }
}