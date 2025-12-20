<?php
/**
 * Simple XLSX writer (OpenXML without external library)
 * Creates minimal but valid .xlsx files
 */

class SimpleXLSX {
    private $sheets = [];
    private $sheetIndex = 0;
    
    public function addSheet($name) {
        $this->sheets[$this->sheetIndex] = [
            'name' => $name,
            'rows' => []
        ];
        return $this->sheetIndex++;
    }
    
    public function addRow($sheetIdx, $cells) {
        if (!isset($this->sheets[$sheetIdx])) return false;
        $this->sheets[$sheetIdx]['rows'][] = $cells;
        return true;
    }
    
    public function output($filename = 'file.xlsx') {
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        
        if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
            die('Không thể tạo file XLSX');
        }
        
        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        
        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);
        
        // docProps/core.xml
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Laptop Store</dc:creator>'
            . '<cp:lastModifiedBy>Laptop Store</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-dT H:i:sZ') . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('Y-m-dT H:i:sZ') . '</dcterms:modified>'
            . '</cp:coreProperties>';
        $zip->addFromString('docProps/core.xml', $core);
        
        // xl/styles.xml (minimal)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);
        
        // xl/workbook.xml & sheet rels
        $sheetNames = [];
        $sheetRels = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheetNames[] = '<sheet name="' . htmlspecialchars($sheet['name']) . '" sheetId="' . ($i+1) . '" r:id="rId' . ($i+2) . '"/>';
            $sheetRels .= '<Relationship Id="rId' . ($i+2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i+1) . '.xml"/>';
        }
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<fileVersion appName="xl" lastEdited="4" lowestEdited="4" rupBuild="4505"/>'
            . '<workbookPr defaultTheme="1"/>'
            . '<sheets>' . implode('', $sheetNames) . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);
        
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . $sheetRels
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        
        // xl/worksheets/sheetN.xml
        foreach ($this->sheets as $i => $sheet) {
            $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>';
            
            foreach ($sheet['rows'] as $rowIdx => $cells) {
                $sheetXml .= '<row r="' . ($rowIdx + 1) . '">';
                foreach ($cells as $colIdx => $value) {
                    $colLetter = $this->getColumnLetter($colIdx);
                    $cellRef = $colLetter . ($rowIdx + 1);
                    $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars($value) . '</t></is></c>';
                }
                $sheetXml .= '</row>';
            }
            
            $sheetXml .= '</sheetData></worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . ($i+1) . '.xml', $sheetXml);
        }
        
        // Dummy theme
        $theme = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme"/>';
        $zip->addFromString('xl/theme/theme1.xml', $theme);
        
        $zip->close();
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
    
    private function getColumnLetter($index) {
        $letters = '';
        while ($index >= 0) {
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = (int)($index / 26) - 1;
        }
        return $letters;
    }
}
