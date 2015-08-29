<?php

/**
 * EasyCsv is a php utility that provides simple csv file manipulation methods
 * including reading writing and searching csv file. internally treating the csv file
 * as a 2D array.
 *
 * @author nblackwe https://people.ok.ubc.ca/nblackwe
 * @license MIT
 *
 * @tutorial
 *
 * $csv=EasyCsv::OpenCSV($filename); //you can now use EasyCsv methods with $csv.
 *
 * //get dimensions.
 * $height=EasyCsv::CountRows($csv);
 * $width=EasyCsv::CountColumns($csv);
 */
class EasyCsv {

    public static function OpenCsv($filename, $options = array()) {

        $default = array(
            'hasHeader' => true,
            'length' => 0
        );
        // , 'encoding'=>'UTF-8'
        
        $csv = array_merge(array(
            'rows' => array()
        ), $default, array_intersect_key($options, $default));
        
        if (key_exists('hasHeader', $csv) && $csv['hasHeader']) {
            $csv['header'] = array();
        }
        $bac_ = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', TRUE);
        $handle = fopen($filename, "r");
        // stream_encoding($handle, $csv['encoding']);
        
        if ($handle !== false) {
            $c = 0;
            while (($data = fgetcsv($handle, 0, ",")) !== false) {
                
                if (key_exists('header', $csv) && count($csv['header']) == 0) {
                    $c ++;
                    $csv['header'] = $data;
                    if ($csv['length'] == 0)
                        $csv['length'] = count($data);
                } else {
                    $c ++;
                    $csv['rows'][] = $data;
                    $count = count($data);
                    if ($count > $csv['length'] && $csv['length'] > 0) {
                        throw new Exception(
                            'CSV file contians longer than expected: (' . $csv['length'] . ':' . $count . ')');
                    }
                }
            }
            fclose($handle);
            ini_set('auto_detect_line_endings', $bac_);
            if ($c <= 0) {
                throw new Exception('0 rows in document');
            }
        } else {
            ini_set('auto_detect_line_endings', $bac_);
            throw new Exception('Invalid File, or Failed to read');
        }
        return $csv;
    }

    public static function CreateCSV($header = false) {

        $csv = array(
            'rows' => array()
        );
        if ($header) {
            $csv['header'] = $header;
        }
        return $csv;
    }

    public static function AddRow(&$csv, $row) {

        $csv['rows'][] = $row;
    }

    public static function Write($csv) {

        return EasyCsv::_str_putcsv($csv);
    }

    private static function _str_putcsv($csv) {
        // Generate CSV data from array
        $fh = fopen('php://temp', 'rw'); // don't create a file, attempt
                                         // to use memory instead
        fputcsv($fh, $csv['header']);
        foreach ($csv['rows'] as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        
        return $csv;
    }

    public static function GetFieldNames($csvOrFilename, $csvMetdata = false) {

        $csv = $csvOrFilename;
        
        if (is_array($csv)) {
            
            if (key_exists('header', $csv))
                return $csv['header'];
            throw new Exception('Failed to find $field[\'header\']: ' . print_r(array_keys($csv), true));
        } elseif (is_string($csv)) {
            
            $fileContents = false;
            // $len=is_array($csvMetdata)&&key_exists('length', $csvMetdata)?$csvMetdata['length']:0;
            
            if (file_exists($csv) && is_file($csv)) {
                
                ini_set('auto_detect_line_endings', TRUE);
                $handle = fopen($filename, "r");
                ini_set('auto_detect_line_endings', $bac_);
                
                if ($handle !== false) {
                    $data = fgetcsv($handle, 0, ",");
                    fclose($handle);
                    if ($data !== false) {
                        return $data;
                    }
                    throw new Exception('0 rows in document');
                } else {
                    throw new Exception('Invalid File, or Failed to read');
                }
            } else {
                throw new Exception('File not found: ' . $csv);
            }
        }
        return false;
    }

    public static function CountRows($csv) {

        return count($csv['rows']);
    }

    public static function CountColumns($csv) {

        if ($csv['length'] > 0) {
            return $csv['length'];
        }
        return (count($csv['rows']) > 0 ? count($csv['rows'][0]) : 0);
    }

    public static function GetHeader($csv) {

        return $csv['header'];
    }

    /**
     *
     * @param array $csv
     *            as returned by OpenCSV or CreateCSV
     * @param int $index
     *            row number starting at 0, not including header if it exists
     * @return NULL
     */
    public static function GetRow($csv, $index) {

        $row = $csv['rows'][$index];
        return EasyCsv::_pad($row, $csv['header']);
    }

    private static function _pad($row, $header) {

        while (count($row) < count($header)) {
            $row[] = null;
        }
        return $row;
    }

    public static function ColumnIndexOf($csv, $fieldName) {

        foreach ($csv['header'] as $i => $field) {
            if ($field == $fieldName) {
                return $i;
            }
        }
        return -1;
    }

    /**
     *
     * @param array $csv
     *            csv object
     * @param int $index
     *            row index
     * @param array $fieldNames
     *            ordered array of names to accociate cell values (optional will use csv)
     * @return array an string indexed array
     */
    public static function GetRow_Assoc($csv, $index, $fieldNames = false) {

        if (!$fieldNames)
            $fieldNames = $csv['header'];
        return EasyCsv::_combine($fieldNames, EasyCsv::GetRow($csv, $index));
    }

    private static function _combine($header, $row) {

        while (count($row) < count($header)) {
            $row[] = null;
        }
        if (count($header) != count($row)) {
            throw new Exception(
                'Expected number of fields to match header[' . implode(',', $header) . ']:' . count($header) . ' row@?[' .
                     implode(',', $row) . ']:' . count($row));
        }
        return array_combine($header, $row);
    }

    public static function GetMatchingRowKeys($csv, $match, $fieldNames = false) {

        if (!$fieldNames)
            $fieldNames = $csv['header'];
        $keys = array();
        
        if ((!key_exists('rows', $csv)) || (!is_array($csv['rows'])))
            throw new Exception('Invalid $csv[\'rows\']');
        
        foreach ($csv['rows'] as $index => $row) {
            $row_a = EasyCsv::_combine($fieldNames, $row);
            $true = true;
            foreach ($match as $k => $v) {
                if ($row_a[$k] != $v) {
                    $true = false;
                    break;
                }
            }
            if ($true)
                $keys[] = $index;
        }
        
        return $keys;
    }

    public static function IterateRows($csv, $callback) {

        for ($i = 0; $i < EasyCsv::CountRows($csv); $i ++) {
            
            $continue = $callback(EasyCsv::GetRow($csv, $i), $i);
            if ($continue === false)
                break;
        }
    }

    public static function IterateRows_Assoc($csv, $callback) {

        for ($i = 0; $i < EasyCsv::CountRows($csv); $i ++) {
            
            $continue = $callback(EasyCsv::GetRow_Assoc($csv, $i), $i);
            if ($continue === false)
                break;
        }
    }
}