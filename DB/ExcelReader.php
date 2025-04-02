<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
    private $filePath;
    private $worksheet;
    private $data = [];

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function readFile() {
        try {
            $this->worksheet = IOFactory::load($this->filePath)->getActiveSheet();
            $this->extractData();
            return $this->data;
        } catch (Exception $e) {
            throw new Exception("Error reading Excel file: " . $e->getMessage());
        }
    }

    private function extractData() {
        $highestRow = $this->worksheet->getHighestRow();
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= 32; $col++) {
                $rowData[] = $this->worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row)->getValue();
            }
            $this->data[] = $rowData;
        }
    }
} 