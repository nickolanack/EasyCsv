<?php

class CsvTest extends PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testCreateCsv() {

        include_once dirname(__DIR__) . '/easycsv/EasyCsv.php';
        $csv = EasyCsv::CreateCSV(array(
            'one',
            'two',
            'three'
        ));
        EasyCsv::AddRow($csv, array(
            '1',
            '2',
            '3'
        ));
        EasyCsv::AddRow($csv, array(
            '4',
            '5',
            '6'
        ));
        EasyCsv::AddRow($csv, array(
            '7',
            '8',
            '9'
        ));
        
        $this->assertEquals(3, EasyCsv::CountRows($csv));
        $this->assertEquals('5', EasyCsv::GetRow_Assoc($csv, 1)['two']);
        $this->assertEquals('5', EasyCsv::GetRow($csv, 1)[1]);
        
        $asserts = array();
        EasyCsv::IterateRows_Assoc($csv, 
            function ($row_assoc, $i) use(&$asserts) {
                
                if ($i == 0) {
                    $asserts[] = array(
                        array(
                            'one' => '1',
                            'two' => '2',
                            'three' => '3'
                        ),
                        $row_assoc
                    );
                }
                if ($i == 1) {
                    $asserts[] = array(
                        array(
                            'one' => '4',
                            'two' => '5',
                            'three' => '6'
                        ),
                        $row_assoc
                    );
                }
                if ($i == 2) {
                    $asserts[] = array(
                        array(
                            'one' => '7',
                            'two' => '8',
                            'three' => '9'
                        ),
                        $row_assoc
                    );
                }
            });
        
        foreach ($asserts as $assert) {
            $this->assertEquals($assert[0], $assert[1]);
        }
    }
}