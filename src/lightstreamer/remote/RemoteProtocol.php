<?php
/*
 Copyright 2015 Weswit Srl

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */
namespace lightstreamer\remote;

class RemoteProtocol
{

    static function sendReply($control, $reply)
    {
        echo "Reply = $reply";
        fputs($control, $reply);
    }

    static function parse_request($request)
    {
        $request = rtrim($request);
        echo "Request = [$request]\n";
        $tokens = explode('|', $request);
        $sid = $tokens[0];
        return array(
            "id" => $sid,
            "method" => $tokens[1],
            "data" => array_slice($tokens, 2)
        );
    }

    static function map($tokens, $start)
    {
        $data = array_slice($tokens, $start);
        $session_info = array();
        for ($i = 1; $i < count($data) - 2; $i += 4) {
            $key = $data[$i];
            $session_info[$key] = self::decodeString($data[$i + 2]);
        }
        
        return $session_info;
    }

    static function decodeString($string)
    {
        if ($string == "#") {
            return null;
        }
        
        if ($string == "$") {
            return "";
        }
        
        return urldecode($string);
    }

    static function encodeString($string)
    {
        if (is_null($string)) {
            return "#";
        }
        
        if (empty($string)) {
            return "$";
        }
        
        return urlencode($string);
    }

    static function encodeBoolean($bool)
    {
        return $bool ? "1" : "0";
    }

    static function encodeDouble($double)
    {
        return strval($double);
    }

    static function encodeModes($modes)
    {
        if (is_null($modes)) {
            return "#";
        }
        
        if (count($modes) == 0) {
            return "$";
        }
        
        $modeStr = "";
        foreach ($modes as $mode) {
            switch ($mode) {
                case "RAW":
                    $modeStr .= "R";
                    break;
                case "MERGE":
                    $modeStr .= "M";
                    break;
                case "DISTINCT":
                    $modeStr .= "D";
                    break;
                case "COMMAND":
                    $modeStr .= "C";
                    break;
            }
        }
        return $modeStr;
    }

    static function readInit($data)
    {
        $params = self::map($data, 0);
        return $params;
    }
}
?>