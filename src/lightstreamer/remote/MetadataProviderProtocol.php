<?php
/*
 * Copyright 2015 Weswit Srl
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace lightstreamer\remote;

class MetadataProviderProtocol extends RemoteProtocol
{

    static function writeInit()
    {
        $response = "MPI|V";
        return $response;
    }

    static function writeInitWithException(\Exception $e)
    {
        $response = "MPI|E";
        if ($e instanceof MetadataProviderException) {
            $response .= "M";
        }
        $response .= "|" . self::encodeString($e->getMessage());
        return $response;
    }

    static function readNotifyUserSession($data)
    {
        $value = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "password" => self::decodeString(self::read($data, "S", 2)),
            "httpHeaders" => self::map($data, 4)
        );
        
        return $value;
    }

    static function writeNotiyUserSession($method, $allowedMaxBandwidth, $wantsTablesNotification)
    {
        $encodedAllowedMaxBandwidth = self::encodeDouble($allowedMaxBandwidth);
        $encodedWantsTablesNotification = self::encodeBoolean($wantsTablesNotification);
        $response = "$method|D|$encodedAllowedMaxBandwidth|B|$encodedWantsTablesNotification";
        return $response;
    }

    static function writeNotiyUserSessionWithException($method, \Exception $e)
    {
        $response = "$method|E";
        
        if ($e instanceof AccessException) {
            $response .= "A";
        } elseif ($e instanceof CreditsException) {
            $response .= "C";
        }
        $response .= "|" . self::encodeString($e->getMessage());
        if ($e instanceof CreditsException) {
            $response .= "|" . $e->getCode() . "|" . self::encodeString($e->getClientUserMsg());
        }
        return $response;
    }

    static function readNotifyUserAuthorization($data)
    {
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "password" => self::decodeString(self::read($data, "S", 2)),
            "clientPrincipal" => self::decodeString(self::read($data, "S", 4)),
            "httpHeaders" => self::map($data, 6)
        );
        
        return $values;
    }

    static function readNotifyNewSession($data)
    {
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "session_id" => self::decodeString(self::read($data, "S", 2)),
            "clientContext" => self::map($data, 4)
        );
        
        return $values;
    }

    static function writeNotifyNewSession()
    {
        return "NNS|V";
    }

    static function writeNotifyNewSessionWithException(\Exception $e)
    {
        $response = "NNS|E";
        
        if ($e instanceof NotificationException) {
            $response .= "N";
        } elseif ($e instanceof CreditsException) {
            if ($e instanceof ConflictingSessionException) {
                $response .= "X";
            } else {
                $response .= "C";
            }
        }
        $response .= "|" . self::encodeString($e->getMessage());
        if ($e instanceof CreditsException) {
            $response .= "|" . $e->getCode() . "|" . self::encodeString($e->getClientUserMsg());
            if ($e instanceof ConflictingSessionException) {
                $response .= "|" . self::encodeString($e->getConflictingSessionID());
            }
        }
        return $response;
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

    static function writeNotifySessionCloseWithException(\Exception $e)
    {
        $response = "NSC|E";
        
        if ($e instanceof NotificationException) {
            $response .= "N|" . self::encodeString($e->getMessage());
        }
        
        return $response;
    }

    static function readGetItems($data)
    {
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "group" => self::decodeString(self::read($data, "S", 2)),
            "session_id" => self::decodeString(self::read($data, "S", 4))
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

    static function writeGetItemsWithException(\Exception $e)
    {
        $response = "GIS|E";
        
        if ($e instanceof ItemsException) {
            $response .= "I";
        }
        
        $response .= "|" . self::encodeString($e->getMessage());
        
        return $response;
    }

    static function readGetSchema($data)
    {
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "group" => self::decodeString(self::read($data, "S", 2)),
            "schema" => self::decodeString(self::read($data, "S", 4)),
            "session_id" => self::decodeString(self::read($data, "S", 6))
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

    static function writeGetSchemaWithException(\Exception $e)
    {
        $response = "GSC|E";
        
        if ($e instanceof ItemsException) {
            $response .= "I";
        } elseif ($e instanceof SchemaException) {
            $response .= "S";
        }
        
        $response .= "|" . self::encodeString($e->getMessage());
        
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

    private static function readTable($table, $offset)
    {
        $winIndex = self::read($table, "I", $offset);
        $mode = self::read($table, "M", $offset + 2);
        $group = self::decodeString(self::read($table, "S", $offset + 4));
        $schema = self::decodeString(self::read($table, "S", $offset + 6));
        $firstItemIndex = self::read($table, "I", $offset + 8);
        $lastItemIndex = self::read($table, "I", $offset + 10);
        
        $tableInfo = new TableInfo($winIndex, $mode, $group, $schema, $firstItemIndex, $lastItemIndex);
        return $tableInfo;
    }

    private static function readTables($data, $offset)
    {
        $tablesSegment = array_slice($data, $offset);
        $tableChunks = array_chunk($tablesSegment, 7);
        
        $tableInfos = array();
        foreach ($tableChunks as $table) {
            $tableInfo = self::readTable($table, 0);
            array_push($tableInfos, $tableInfo);
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

    static function decodeMobilePlatformType($string)
    {
        if (strlen($string) != 1) {
            throw new \RuntimeException("String length invalid!");
        }
        
        $pos = strpos("AG", $string);
        if ($pos !== FALSE) {
            return $string;
        } else {
            throw new \RuntimeException("Found invalid mobile platform type [$string]");
        }
    }

    private static function readMpnDeviceInfo($data, $offset = 0)
    {
        $mobilePlatformType = self::decodeMobilePlatformType(self::read($data, "P", $offset));
        $applicationId = self::decodeString(self::read($data, "S", $offset + 2));
        $deviceToken = self::decodeString(self::read($data, "S", $offset + 4));
        
        $mpnDeviceInfo = new MpnDeviceInfo($mobilePlatformType, $applicationId, $deviceToken);
        
        return $mpnDeviceInfo;
    }

    static function readNotifyDeviceAccess($data)
    {
        $mpnDeviceInfo = self::readMpnDeviceInfo($data, 2);
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "mpnDeviceInfo" => $mpnDeviceInfo
        );
        
        return $values;
    }

    static function writeNotifyDeviceAccess()
    {
        $response = "MDA|V";
        return $response;
    }

    private static function readMobileGcmSubscriptionInfo($data, $offset)
    {
        $argLength = $data[$offset];
        $arguments = self::seq($data, $offset + 22, $argLength * 2);
        
        $custDataSize = $data[$offset + 1];
        $customData = self::map($data, $offset + 22 + $argLength * 2, $custDataSize * 4);
        
        $device = self::readMpnDeviceInfo($data, $offset + 2);
        $trigger = self::decodeString(self::read($data, "S", $offset + 8));
        $sound = self::decodeString(self::read($data, "S", $offset + 10));
        $badge = self::decodeString(self::read($data, "S", $offset + 12));
        $localizedActionKey = self::decodeString(self::read($data, "S", $offset + 14));
        $launchImage = self::decodeString(self::read($data, "S", $offset + 16));
        $format = self::decodeString(self::read($data, "S", $offset + 18));
        $localizedFormatKey = self::read($data, "S", $offset + 20);
        
        $mpnSubscriptionInfo = new MpnGcmSubscriptionInfo($device, $trigger, $sound, $badge, $localizedActionKey, $launchImage, $format, $localizedFormatKey, $arguments, $customData);
        
        return $mpnSubscriptionInfo;
    }

    private static function readMobileApnSubscriptionInfo($data, $offset)
    {
        $numOfData = $data[$offset];
        
        $device = self::readMpnDeviceInfo($data, $offset + 1);
        $trigger = self::decodeString(self::read($data, "S", $offset + 7));
        $collapseKey = self::decodeString(self::read($data, "S", $offset + 9));
        $dataMap = self::map($data, $offset + 11, $numOfData * 4);
        $next = $offset + 13 + $numOfData * 4;
        $delayWhileIdle = self::decodeString(self::read($data, "S", $next));
        $timeToLive = self::decodeString(self::read($data, "S", $offset + 13 + $numOfData * 4));
        
        $mpnApnSubscriptionInfo = new MpnApnSubscriptionInfo($device, $trigger, $collapseKey, $dataMap, $delayWhileIdle, $timeToLive);
        
        return $mpnApnSubscriptionInfo;
    }

    static function readNotifySubscriptionActivation($data)
    {
        $values = array();
        
        $values["user"] = self::decodeString(self::read($data, "S", 0));
        $values["session_id"] = self::decodeString(self::read($data, "S", 2));
        $values["table"] = self::readTable($data, 4);
        
        $subscriptionType = $data[16];
        switch ($subscriptionType) {
            case "PA":
                $mpnSubscriptionInfo = self::readMobileGcmSubscriptionInfo($data, 17);
                $values["subscription"] = $mpnSubscriptionInfo;
                break;
            
            case "PG":
                $mpnSubscriptionInfo = self::readMobileApnSubscriptionInfo($data, 17);
                $values["subscription"] = $mpnSubscriptionInfo;
                break;
        }
        
        return $values;
    }

    static function writeNotifySubscriptionActivation()
    {
        $response = "MSA|V";
        
        return $response;
    }

    static function readNotifyDeviceTokenChange($data)
    {
        $mpnDeviceInfo = self::readMpnDeviceInfo($data, 2);
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "mpnDeviceInfo" => $mpnDeviceInfo,
            "newDeviceToken" => self::decodeString(self::read($data, "S", 8))
        );
        
        return $values;
    }

    static function writeNotifyDeviceTokenChange()
    {
        $response = "MDC|V";
        return $response;
    }
}
?>