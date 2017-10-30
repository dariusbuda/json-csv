<?php
/**
 * Created by PhpStorm.
 * User: Darius Buda
 * Date: 10/20/2017
 * Time: 8:04 PM
 */

class Log
{
    public static $startTime;
    public static $exportLog = array();

    public static function start($jsonURL, $outputFile, $time, $flag = 'new convert')
    {
        self::$startTime = microtime(true);
        self::$exportLog['jsonURL'] = $jsonURL;
        self::$exportLog['outputFile'] = $outputFile;
        self::$exportLog['start_time'] = $time;
        self::$exportLog['flag'] = $flag;
    }

    public static function writeTimeLog($label)
    {
        $runtime = microtime(true) - self::$startTime;
        self::$exportLog['timeLog'][$label] = sprintf("%.4f", $runtime)." seconds";
    }

    public static function end($time)
    {
        self::$exportLog['end_time'] = $time;
    }

    public static function logsToJSON()
    {
        try {
            Util::openFile('logs/logs.json', 'r+');
            $logs = json_decode(file_get_contents('logs/logs.json'), true);
            $logs[] = self::$exportLog;
            file_put_contents('logs/logs.json', json_encode($logs));
        }
        catch(Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function printLogs()
    {
        try {
            Util::openFile('logs/logs.json', 'r');
            return json_decode(file_get_contents('logs/logs.json'), true);
        }
        catch(Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}

class Util
{
    public static function openFile($file, $mode = 'w+')
    {
        $fp = null;
        $fp = @fopen($file, $mode);

        if(!$fp) {
            $err = error_get_last();
            throw new Exception($err['message']);
        }

        return $fp;
    }

    public static function curlConnectTo($jsonURL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $jsonURL);
        $result = curl_exec($ch);

        if(curl_errno($ch)) {
            throw new Exception('Could not access JSON URL. Error: '.curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    public static function decodeJSON($json, $assoc = false)
    {
        $decodedJSON = json_decode($json, $assoc);

        if(json_last_error() != JSON_ERROR_NONE) {
            throw new Exception('JSON not valid. Error: '.json_last_error_msg());
        }

        return $decodedJSON;
    }

    public static function arrayDiffAssocRecursive($array1, $array2) {
        $difference=array();
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::arrayDiffAssocRecursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }
}

/**
 * Class ConvertAbstract
 * Abstract class for converting JSON to different formats (XML, CSV, TSV etc)
 * Each format will have to be represented by a class that will extend the ConvertAbstract class and implement the _saveToDisk method
 */
abstract class ConvertAbstract
{
    /**
     * @var
     * json source URL
     */
    protected $jsonURL;

    /**
     * @var
     * used only by 'check for updates' cron
     * There are at least two files in this folder:
     * - the first file is called sources.csv, which is populated with sources url's(each separated by comma)
     * - the following files are json files and contain jsons from those sources
     * The cron will check if anything has changed in the source url by comparing with what's in the json corresponding file
     */
    protected $jsonFolderPath;

    /**
     * @var
     * the folder where the converted json file is saved. It varies from one class to another
     */
    protected $outputFileFolder;

    /**
     * @var
     * the converted filename
     */
    protected $outputFileName;

    /**
     * @var
     * the converted file type
     */
    protected $outputFileType;

    /**
     * @var
     * fopen output file pointer
     */
    protected $outputFilePointer;

    /**
     * @var array
     * array containing all valid jsons
     */
    protected $validDecodedJSON = array();

    /**
     * @return mixed
     * abstract function - must be implemented by each class
     * saves the outputFileName.outputFileType file in outputFolder
     */
    abstract public function _saveToDisk();

    public function __construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName, $outputFileType)
    {
        $this->jsonURL = $jsonURL;
        $this->jsonFolderPath = $jsonFolderPath;
        $this->outputFolder = $outputFolder;
        $this->outputFileName = $outputFileName;
        $this->outputFileType = $outputFileType;

        $this->_init();
    }

    private function _init()
    {
        if($this->jsonFolderPath) {
            try {
                if($this->jsonURL) {
                    $result = Util::curlConnectTo($this->jsonURL);
                    $this->validDecodedJSON[] = Util::decodeJSON($result);

                    $this->saveURLToSources();
                    $this->saveJSONAsFile($result);
                }

                $this->outputFilePointer = Util::openFile($this->outputFolder.'/'.$this->outputFileName.'.'.$this->outputFileType);
            }
            catch(Exception $ex) {
                throw new Exception($ex->getMessage());
            }
        }
        else {
            throw new Exception('JSON folder is not defined');
        }
    }

    /**
     * function that saves all json source urls in jsonFolderPath/sources.json
     */
    private function saveURLToSources()
    {
        try {
            Util::openFile($this->jsonFolderPath.'/sources.json', 'r+');
            $sources = json_decode(file_get_contents($this->jsonFolderPath.'/sources.json'), true);
            $sources[$this->jsonURL][] = $this->outputFileName; //the source.json file will retain both the source url and the file name that was converted from that source. We need that at 'check for updates' to know exactly which file we have to update, if it's necessary
            $sources[$this->jsonURL] = array_unique($sources[$this->jsonURL]);
            file_put_contents($this->jsonFolderPath.'/sources.json', json_encode($sources));
        }
        catch(Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * function that creates a JSON file from a jsonURL source
     * the file will be created in jsonFolderPath
     * JSON will be saved with the same name as the converted file
     */
    private function saveJSONAsFile($json)
    {
        try {
            Util::openFile($this->jsonFolderPath.'/'.$this->outputFileName.'.json');
            file_put_contents($this->jsonFolderPath.'/'.$this->outputFileName.'.json', $json);
        }
        catch(Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}

class CSV extends ConvertAbstract
{
    protected $outputFolder;
    protected $outputFileName;
    private $tsv;

    public function __construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName, $tsv = false)
    {
        parent::__construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName, !$tsv ? 'csv' : 'tsv');
    }

    public function _saveToDisk()
    {
        $retArr = array();
        foreach($this->validDecodedJSON as $decodedJSON) {
            $rows = array();
            foreach($decodedJSON as $prop) {
                if(!is_array($prop)) {
                    continue;
                }

                $rows[0] = array();
                foreach($prop as $key => $it) {
                    $this->arrayForCSV($it, $rows, $key + 1, 0);
                }
            }

            fputcsv($this->outputFilePointer, $rows[0], $this->tsv ? chr(9) : ',');

            foreach($rows as $index => $row) {
                if($index == 0) continue;
                fputcsv($this->outputFilePointer, $row, $this->tsv ? chr(9) : ',');
            }
//            Log::writeTimeLog('fputcsv');

            $retArr['rows'] = !empty($retArr['rows']) ? $retArr['rows'] + count($rows) : count($rows);
        }

        return $retArr;
    }

    private function arrayForCSV($arr, &$rows, $rowLevel, $colLevel)
    {
//        Log::writeTimeLog('arrayForCSV_'.$colLevel);
        foreach($arr as $k => $v) {
            if(is_array($v) || is_object($v)) {
                $this->arrayForCSV($v, $rows, $rowLevel, $colLevel + 1);
            }
            else {
                $colValue = '';
                if($colLevel > 0) {
                    $c = $colLevel;
                    while($c > 0) {
                        $colValue .= ($c--).'/';
                    }
                }
                $colValue .= $k;

                $rowValue = $v;

                if(!in_array($colValue, $rows[0])) {
                    $rows[0][] = $colValue;
                }

                $rows[$rowLevel][] = $rowValue;
            }
        }
    }
}

class TSV extends CSV
{
    public function __construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName)
    {
        parent::__construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName, 1);
    }
}

class XML extends ConvertAbstract
{
    protected $outputFolder;

    public function __construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName)
    {
        parent::__construct($jsonURL, $jsonFolderPath, $outputFolder, $outputFileName, 'xml');

        $this->outputFolder = $outputFolder;
        $this->outputFileName = $outputFileName;
    }

    public function _saveToDisk()
    {
        $retArr = array();
        foreach($this->validDecodedJSON as $arr) {
            $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
            $this->arrayToXML($arr, $xml);
            $xml->asXML($this->outputFolder.'/'.$this->outputFileName.'.'.$this->outputFileType);
        }

        return $retArr;
    }

    private function arrayToXML($arr, &$xml)
    {
        foreach($arr as $key => $value) {
            if(is_numeric($key)){
                $key = 'item'.$key;
            }
            if(is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } elseif(!is_object($value)) {
                $xml->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
}

class CheckForUpdate
{
    public static $jsonFolderPath;
    public static $outputFolder;
    public static $exportTypes;

    public static function run()
    {
        if(self::$jsonFolderPath) {
            try {
                Util::openFile(self::$jsonFolderPath.'/sources.json', 'r');
                $sources = json_decode(file_get_contents(self::$jsonFolderPath.'/sources.json'), true);
                if(!empty($sources)) {
                    foreach($sources as $jsonURL => $outputFileNames) {
                        $s = Util::decodeJSON(Util::curlConnectTo($jsonURL), true);
                        foreach($outputFileNames as $outputFileName) {
                            Util::openFile(self::$jsonFolderPath.'/'.$outputFileName.'.json', 'r');
                            $d = json_decode(file_get_contents(self::$jsonFolderPath.'/'.$outputFileName.'.json'), true);

                            $diff = Util::arrayDiffAssocRecursive($s, $d);

                            if(count($diff)) {
                                //refresh destination json and all of the converted files from this json
                                foreach(self::$exportTypes as $outputType => $arr) {
                                    if(!empty($arr['class'])) {
                                        if(file_exists(self::$outputFolder.'/'.$outputFileName.'.'.$outputType)) {
                                            $outputClassName = $arr['class'];
                                            if($outputClassName && class_exists($outputClassName)) {
                                                Log::start($jsonURL, self::$outputFolder.'/'.$outputFileName.'.'.$outputType, date('Y-m-d H:i:s'), 'check for update');
                                                $outputObj = new $outputClassName($jsonURL, self::$jsonFolderPath, self::$outputFolder, $outputFileName);
                                                Log::writeTimeLog('definitions');
                                                $outputObj->_saveToDisk();
                                                Log::writeTimeLog('save_to_disk');
                                                Log::end(date('Y-m-d H:i:s'));

                                                Log::logsToJSON();
                                            }
                                            else {
                                                throw new Exception('Class '.$outputClassName.' not found');
                                            }
                                        }
                                    }
                                    else {
                                        throw new Exception('Export types not defined');
                                    }
                                }
                            }
                            else {
                                Log::start($jsonURL, self::$outputFolder.'/'.$outputFileName, date('Y-m-d H:i:s'), 'check for update - no differences found');
                                Log::end(date('Y-m-d H:i:s'));
                                Log::logsToJSON();
                            }
                        }
                    }
                }
                else {
                    throw new Exception('No sources');
                }
            }
            catch(Exception $ex) {
                throw new Exception($ex->getMessage());
            }
        }
        else {
            throw new Exception('JSON folder is not defined');
        }
    }
}

class Problem0
{
    public function __construct($exportTypes) {
        $this->exportTypes = $exportTypes;
    }

    public function run($params)
    {
        if(!empty($this->exportTypes)) {
            if(!empty($params['outputType'])) {
                if(!empty($this->exportTypes[$params['outputType']]['class'])) {
                    $outputClassName = $this->exportTypes[$params['outputType']]['class'];
                }

                if($outputClassName && class_exists($outputClassName)) {
                    Log::start(!empty($params['jsonURL']) ? $params['jsonURL'] : null, $params['outputFolder']."/".$params['outputFileName'].".".$params['outputType'], date('Y-m-d H:i:s'));
                    $outputObj = new $outputClassName(!empty($params['jsonURL']) ? $params['jsonURL'] : null, !empty($params['jsonFolderPath']) ? $params['jsonFolderPath'] : null, $params['outputFolder'], $params['outputFileName']);
                    Log::writeTimeLog('definitions');
                    $ret = $outputObj->_saveToDisk();
                    Log::writeTimeLog('save_to_disk');
                    Log::end(date('Y-m-d H:i:s'));

                    Log::logsToJSON();

                    return $ret;
                }
                else {
                    throw new Exception('OutputType('.$params['outputType'].') not implemented');
                }
            }
            else {
                throw new Exception('OutputType not defined');
            }
        }
        else {
            throw new Exception('_CONFIG_EXPORT_TYPES not defined');
        }
    }
}
