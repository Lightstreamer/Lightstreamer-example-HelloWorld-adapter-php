<?php
/*
 * Copyright (c) Lightstreamer Srl
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
namespace lightstreamer\adapters\remote;

class MetadataProviderProtocol extends RemoteProtocol
{

    private static function appendExceptions($response, \Exception $e, $subtype = true)
    {
        if ($subtype === true) {
            if ($e instanceof MetadataProviderException) {
                $response .= "M";
            } elseif ($e instanceof NotificationException) {
                $response .= "N";
            } elseif ($e instanceof AccessException) {
                $response .= "A";
            } elseif ($e instanceof ItemsException) {
                $response .= "I";
            } elseif ($e instanceof SchemaException) {
                $response .= "S";
            } elseif ($e instanceof CreditsException) {
                if ($e instanceof ConflictingSessionException) {
                    $response .= "X";
                } else {
                    $response .= "C";
                }
            }
        }
        $response .= "|" . self::encodeString($e->getMessage());
        
        if ($subtype === true) {
            if ($e instanceof CreditsException) {
                $response .= "|" . $e->getCode() . "|" . self::encodeString($e->getClientUserMsg());
                if ($e instanceof ConflictingSessionException) {
                    $response .= "|" . self::encodeString($e->getConflictingSessionID());
                }
            }
        }
        
        return $response;
    }

    static function writeInit()
    {
        return "MPI|V";
    }

    static function writeInitWithException(\Exception $e)
    {
        return self::appendExceptions("MPI|E", $e);
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
        return self::appendExceptions("$method|E", $e);
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
        return self::appendExceptions("NNS|E", $e);
    }

    static function readNotifiySessionClose($data)
    {
        return self::decodeString(self::read($data, "S", 0));
    }

    static function writeNotifySessionClose()
    {
        return "NSC|V";
    }

    static function writeNotifySessionCloseWithException(\Exception $e)
    {
        return self::appendExceptions("NSC|E", $e);
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
        return self::appendExceptions("GIS|E", $e);
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
        return self::appendExceptions("GSC|E", $e);
    }

    static function readGetItemData($data)
    {
        return self::seq($data, 0);
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

    static function writeGetItemDataWithException(\Exception $e)
    {
        return self::appendExceptions("GIT|E", $e, FALSE);
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

    static function writeGetUserItemDataWithException(\Exception $e)
    {
        return self::appendExceptions("GUI|E", $e, FALSE);
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
        return "NUM|V";
    }

    static function writeNotifyUserMessageWithException(\Exception $e)
    {
        return self::appendExceptions("NUM|E", $e);
    }

    private static function readTable($table, $offset, $withSelector = true)
    {
        $winIndex = self::read($table, "I", $offset);
        $mode = self::read($table, "M", $offset + 2);
        $group = self::decodeString(self::read($table, "S", $offset + 4));
        $schema = self::decodeString(self::read($table, "S", $offset + 6));
        $firstItemIndex = self::read($table, "I", $offset + 8);
        $lastItemIndex = self::read($table, "I", $offset + 10);
        
        $selector = NULL;
        if ($withSelector === TRUE) {
            $selector = self::decodeString(self::read($table, "S", $offset + 12));
        }
        
        $tableInfo = new TableInfo($winIndex, $mode, $group, $schema, $firstItemIndex, $lastItemIndex, $selector);
        return $tableInfo;
    }

    private static function readTables($data, $offset)
    {
        $tablesSegment = array_slice($data, $offset);
        $tableChunks = array_chunk($tablesSegment, 14);
        
        $tableInfos = array();
        foreach ($tableChunks as $table) {
            $tableInfo = self::readTable($table, 0);
            array_push($tableInfos, $tableInfo);
        }
        
        return $tableInfos;
    }

    static function readNotifyNewTables($data)
    {
        $notifyNewTablesData = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "session_id" => self::decodeString(self::read($data, "S", 2)),
            "tableInfos" => self::readTables($data, 4)
        );
        
        return $notifyNewTablesData;
    }

    static function writeNotifyNewTablesData()
    {
        return "NNT|V";
    }

    static function writeNotifyNewTablesDataWithException(\Exception $e)
    {
        return self::appendExceptions("NNT|E", $e);
    }

    static function readNotifyTablesClose($data)
    {
        $notifyTablesCloseData = array(
            "session_id" => self::decodeString(self::read($data, "S", 0)),
            "tableInfos" => self::readTables($data, 2)
        );
        
        return $notifyTablesCloseData;
    }

    static function writeNotifyTablesClose()
    {
        return "NTC|V";
    }

    static function writeNotifyTablesCloseWithException($e)
    {
        return self::appendExceptions("NTC|E", $e);
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
        $mpnDeviceInfo = self::readMpnDeviceInfo($data, 4);
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "session_id" => self::decodeString(self::read($data, "S", 2)),
            "mpnDeviceInfo" => $mpnDeviceInfo
        );
        
        return $values;
    }

    static function writeNotifyDeviceAccess()
    {
        return "MDA|V";
    }

    static function writeNotifyDeviceAccessWithException(\Exception $e)
    {
        return self::appendExceptions("MDA|E", $e);
    }

    private static function readMobileSubscriptionInfo($data, $offset)
    {
        $device = self::readMpnDeviceInfo($data, $offset);
        $trigger = self::decodeString(self::read($data, "S", $offset + 6));
        $notificationFormat = self::decodeString(self::read($data, "S", $offset + 8));
        
        $mpnSubscriptionInfo = new MpnSubscriptionInfo($device, $trigger, $notificationFormat);
        
        return $mpnSubscriptionInfo;
    }

    static function readNotifySubscriptionActivation($data)
    {
        $values = array();
        
        $values["user"] = self::decodeString(self::read($data, "S", 0));
        $values["session_id"] = self::decodeString(self::read($data, "S", 2));
        $values["table"] = self::readTable($data, 4, FALSE);
        
        $mpnSubscriptionInfo = self::readMobileSubscriptionInfo($data, 16);
        $values["subscription"] = $mpnSubscriptionInfo;
        
        return $values;
    }

    static function writeNotifySubscriptionActivation()
    {
        return "MSA|V";
    }

    static function writeNotifySubscriptionActivationWithException(\Exception $e)
    {
        return self::appendExceptions("MSA|E", $e);
    }

    static function readNotifyDeviceTokenChange($data)
    {
        $mpnDeviceInfo = self::readMpnDeviceInfo($data, 4);
        $values = array(
            "user" => self::decodeString(self::read($data, "S", 0)),
            "session_id" => self::decodeString(self::read($data, "S", 2)),
            "mpnDeviceInfo" => $mpnDeviceInfo,
            "newDeviceToken" => self::decodeString(self::read($data, "S", 10))
        );
        
        return $values;
    }

    static function writeNotifyDeviceTokenChange()
    {
        return "MDC|V";
    }

    static function writeNotifyDeviceTokenChangeWithException(\Exception $e)
    {
        return self::appendExceptions("MDC|E", $e);
    }
}
?>