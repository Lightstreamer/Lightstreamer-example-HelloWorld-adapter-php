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

class MetadataProviderProtocol extends RemoteProtocol
{

    static function writeInit()
    {
        $response = "MPI|V";
        return $response;
    }

    static function readNotifyUserSession($data)
    {
        $value = array(
            "user" => self::decodeString($data[1]),
            "password" => self::decodeString($data[3]),
            "httpHeaders" => self::map($data, 4)
        );
        
        return $value;
    }

    private static function writeNotiyUserSessionResponse($method, $allowedMaxBandwidth, $wantsTablesNotification)
    {
        $encodedAllowedMaxBandwidth = self::encodeDouble($allowedMaxBandwidth);
        $encodedWantsTablesNotification = self::encodeBoolean($wantsTablesNotification);
        $response = "$method|D|$encodedAllowedMaxBandwidth|B|$encodedWantsTablesNotification";
        return $response;
    }

    static function writeNotiyUserSession($allowedMaxBandwidth, $wantsTablesNotification)
    {
        return self::writeNotiyUserSessionResponse("NUS", $allowedMaxBandwidth, $wantsTablesNotification);
    }

    static function writeNotiyUserSessionAuth($method, $allowedMaxBandwidth, $wantsTablesNotification)
    {
        return self::writeNotiyUserSessionResponse("NUA", $allowedMaxBandwidth, $wantsTablesNotification);
    }

    static function readNotifyUserAuthorization($data)
    {
        $values = array(
            "user" => self::decodeString($data[1]),
            "password" => self::decodeString($data[3]),
            "clientPrincipal" => self::decodeString($data[5]),
            "httpHeaders" => self::map($data, 6)
        );
        
        return $values;
    }

    static function readNotifyNewSession($data)
    {
        $values = array(
            "user" => self::decodeString($data[1]),
            "session_id" => self::decodeString($data[3]),
            "clientContext" => self::map($data, 4)
        );
        
        return $values;
    }

    static function writeNotifyNewSession()
    {
        return "NNS|V";
    }

    static function readNotifiySessionClose($data)
    {
        return self::decodeString($data[1]);
    }

    static function writeNotifySessionClose()
    {
        $response = "NSC|V";
        
        return $response;
    }

    static function readGetItems($data)
    {
        $values = array(
            "user" => self::decodeString($data[1]),
            "group" => self::decodeString($data[3]),
            "session_id" => self::decodeString($data[5])
        );
        
        return $values;
    }

    static function writeGetItems($items)
    {
        $response = "GIS";
        foreach ($items as $item) {
            $enc_item = self::encodeString($item);
            $response .= "|S|$enc_item";
        }
        
        return $response;
    }

    static function readGetSchema($data)
    {
        $values = array(
            "user" => self::decodeString($data[1]),
            "group" => self::decodeString($data[3]),
            "schema" => self::decodeString($data[5]),
            "session_id" => self::decodeString($data[7])
        );
        
        return $values;
    }

    static function writeGetSchema($fields)
    {
        if ($fields == null) {
            $fields = array();
        }
        
        $response = "GSC";
        foreach ($fields as $field) {
            $enc_field = self::encodeString($field);
            $response .= "|S|$enc_field";
        }
        
        return $response;
    }

    static function readGetItemData($data)
    {
        $decoded_items = array();
        foreach ($data as $item) {
            if ($item != "S") {
                array_push($decoded_items, self::decodeString($item));
            }
        }
        return $decoded_items;
    }

    static function writeGetItemData($itemData)
    {
        $response = "GIT";
        foreach ($itemData as $data) {
            $item = $data["item"];
            $distinctSnapshotLength = $data["distinctSnapshotLength"];
            $minSourceFrequency = self::encodeDouble($data["minSourceFrequency"]);
            $encodedModes = self::encodeModes($data["allowedModeList"]);
            $response .= "|I|$distinctSnapshotLength|D|$minSourceFrequency|M|$encodedModes";
        }
        
        return $response;
    }

    static function readGetUserItemData($data)
    {
        $dataItems = self::readGetItemData($data);
        $items = array_slice($dataItems, 1);
        $values = array(
            "user" => $dataItems[0],
            "items" => $items
        );
        
        return $values;
    }

    static function writeGetUserItemData($itemsData)
    {
        $response = "GUI";
        foreach ($itemsData as $itemData) {
            $item = $itemData["item"];
            $allowedBufferSize = $itemData["allowedBufferSize"];
            $allowedMaxFrequency = self::encodeDouble($itemData["allowedMaxFrequency"]);
            $encodedModes = self::encodeModes($itemData["allowedModeList"]);
            $response .= "|I|$allowedBufferSize|D|$allowedMaxFrequency|M|$encodedModes";
        }
        
        return $response;
    }

    static function readNotifyUserMessage($data)
    {
        $values = array(
            "user" => self::decodeString($data[1]),
            "session_id" => self::decodeString($data[3]),
            "message" => self::decodeString($data[5])
        );
        
        return $values;
    }

    static function writeNotifyUserMessage()
    {
        $response = "NUM|V";
        return $response;
    }

    private static function readTables($data, $offset)
    {
        $tablesSegment = array_slice($data, $offset);
        $tableChunks = array_chunk($tablesSegment, 7);
        
        $tableInfos = array();
        foreach ($tableChunks as $table) {
            $currentToken = $table[0];
            if ($currentToken == "I") {
                $winIndex = $table[1];
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            
            $currentToken = $table[2];
            if ($currentToken == "M") {
                $mode = $table[3];
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            
            $currentToken = $table[4];
            if ($currentToken == "S") {
                $group = self::decodeString($table[5]);
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            
            $currentToken = $table[6];
            if ($currentToken == "S") {
                $schema = self::decodeString($table[7]);
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            
            $currentToken = $table[8];
            if ($currentToken == "I") {
                $firstItemIndex = $table[9];
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            $currentToken = $table[10];
            if ($currentToken == "I") {
                $lastItemIndex = $table[11];
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            $currentToken = $table[12];
            if ($currentToken == "S") {
                $selector = self::decodeString($table[13]);
            } else {
                throw new \RuntimeException("Found invalid token type $currentToken");
            }
            
            $tableInfo = new TableInfo($winIndex, $mode, $group, $schema, $firstItemIndex, $lastItemIndex, $selector);
            array_push($tableInfos, $tableInfo);
            
            $offset += 14;
        }
        
        $notifyNewTablesData["tableInfos"] = $tableInfos;
        return $notifyNewTablesData;
    }

    static function readNotifyNewTables($data)
    {
        $notifyNewTablesData = array(
            "user" => self::decodeString($data[1]),
            "session_id" => self::decodeString($data[3]),
            "tableInfos" => self::readTables($data, 4)
        );
        
        return $notifyNewTablesData;
    }

    static function writeNotifyNewTablesData()
    {
        $response = "NNT|V";
        
        return $response;
    }

    static function readNotifyTablesClose($data)
    {
        $notifyTablesCloseData = array(
            "user" => self::decodeString($data[1]),
            "tableInfos" => self::readTables($data, 2)
        );
        
        return $notifyTablesCloseData;
    }

    static function writeNotifyTablesClose()
    {
        $response = "NTC|V";
        
        return $response;
    }
}
?>