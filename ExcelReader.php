<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
    private $filePath;
    private $spreadsheet;
    private $worksheet;
    private $data;

    public function __construct($filePath) {
        $this->filePath = $filePath;
        $this->data = [];
    }

    public function readFile() {
        try {
            $this->spreadsheet = IOFactory::load($this->filePath);
            $this->worksheet = $this->spreadsheet->getActiveSheet();
            $this->extractData();
            return $this->data;
        } catch (Exception $e) {
            throw new Exception("Error reading Excel file: " . $e->getMessage());
        }
    }

    private function extractData() {
        $highestRow = $this->worksheet->getHighestRow();
        
        // Get the first row to determine number of columns
        $firstRow = $this->worksheet->rangeToArray('A1:' . $this->worksheet->getHighestColumn() . '1');
        $numColumns = count(array_filter($firstRow[0])); // Count only non-empty columns
        
        // Iterate through each row, starting from row 2 (skipping header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            // Iterate through each column
            for ($col = 1; $col <= $numColumns; $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $this->worksheet->getCell($columnLetter . $row)->getValue();
                $rowData[] = $cellValue;
            }
            $this->data[] = $rowData;
        }
    }
} 