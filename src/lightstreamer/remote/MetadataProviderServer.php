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

class MetaDataProviderServer extends Server
{

    private $metadataAdapter;

    public function __construct(IMetaDataProvider $metadataAdapter)
    {
        $this->metadataAdapter = $metadataAdapter;
    }

    public function onReceivedRequest($request)
    {
        $parsed_request = RemoteProtocol::parse_request($request);
        
        $requestId = $parsed_request["id"];
        $method = $parsed_request["method"];
        $data = $parsed_request["data"];
        
        switch ($method) {
            case "MPI":
                $params = MetadataProviderProtocol::readInit($data);
                $this->metadataAdapter->init($params);
                $response = MetadataProviderProtocol::writeInit();
                break;
            
            case "NUS":
                $userSessionData = MetadataProviderProtocol::readNotifyUserSession($data);
                $user = $userSessionData["user"];
                $password = $userSessionData["password"];
                $httpHeaders = $userSessionData["httpHeaders"];
                $this->metadataAdapter->notifyUser($user, $password, $httpHeaders, "");
                
                $allowedMaxBandwidth = $this->metadataAdapter->getAllowedMaxBandwidth($user);
                $wantsTablesNotification = $this->metadataAdapter->wantsTablesNotification($user);
                $response = MetadataProviderProtocol::writeNotiyUserSession($allowedMaxBandwidth, $wantsTablesNotification);
                break;
            
            case "NUA":
                $userSessionData = MetadataProviderProtocol::readNotifyUserAuthorization($data);
                $user = $userSessionData["user"];
                $password = $userSessionData["password"];
                $clientPrincipal = $userSessionData["clientPrincipal"];
                $httpHeaders = $userSessionData["httpHeaders"];
                
                $this->metadataAdapter->notifyUser($user, $password, $httpHeaders, $clientPrincipal);
                
                $response = MetadataProviderProtocol::writeNotiyUserSessionAuth($this->metadataAdapter->getAllowedMaxBandwidth($user), $this->metadataAdapter->wantsTablesNotification($user));
                break;
            
            case "NNS":
                $newSessionData = MetadataProviderProtocol::readNotifyNewSession($data);
                $this->metadataAdapter->notifyNewSession($newSessionData["user"], $newSessionData["session_id"], $newSessionData["clientContext"]);
                $response = MetadataProviderProtocol::writeNotifyNewSession();
                break;
            
            case "NSC":
                $session_id = MetadataProviderProtocol::readNotifiySessionClose($data);
                $this->metadataAdapter->notifySessionClose($session_id);
                $response = MetadataProviderProtocol::writeNotifySessionClose();
                break;
            
            case "GIS":
                $itemsData = MetadataProviderProtocol::readGetItems($data);
                $items = $this->metadataAdapter->getItems($itemsData["user"], $itemsData["session_id"], $itemsData["group"]);
                if (is_null($items)) {
                    $items = array();
                }
                $response = MetadataProviderProtocol::writeGetItems($items);
                break;
            
            case "GSC":
                $schemaData = MetadataProviderProtocol::readGetSchema($data);
                $fields = $this->metadataAdapter->getSchema($schemaData["user"], $schemaData["session_id"], $schemaData["group"], $schemaData["schema"]);
                $response = MetadataProviderProtocol::writeGetSchema($fields);
                break;
            
            case "GIT":
                $items = MetadataProviderProtocol::readGetItemData($data);
                
                $allowedModeList = array();
                $modes = array(
                    "RAW",
                    "MERGE",
                    "DISTINCT",
                    "COMMAND"
                );
                
                $itemsData = array();
                foreach ($items as $item) {
                    $itemData = array();
                    $itemData["item"] = $item;
                    foreach ($modes as $mode) {
                        if ($this->metadataAdapter->modeMayBeAllowed($item, $mode)) {
                            array_push($allowedModeList, $mode);
                        }
                    }
                    $itemData["allowedModeList"] = $allowedModeList;
                    $itemData["distinctSnapshotLength"] = $this->metadataAdapter->getDistinctSnapshotLength($decoded_item);
                    $itemData["minSourceFrequency"] = $this->metadataAdapter->getMinSourceFrequency($decoded_item);
                    array_push($itemsData, $itemData);
                }
                
                $response = MetadataProviderProtocol::writeGetItemData($itemsData);
                break;
            
            case "GUI":
                $userItemData = MetadataProviderProtocol::readGetUserItemData($data);
                $user = $userItemData["user"];
                $items = $userItemData["items"];
                $allowedModeList = array();
                $modes = array(
                    "RAW",
                    "MERGE",
                    "DISTINCT",
                    "COMMAND"
                );
                
                $itemsData = array();
                foreach ($items as $item) {
                    $itemData = array();
                    $itemData["item"] = $item;
                    foreach ($modes as $mode) {
                        if ($this->metadataAdapter->isModeAllowed($user, $item, $mode)) {
                            array_push($allowedModeList, $mode);
                        }
                    }
                    
                    $itemData["allowedModeList"] = $allowedModeList;
                    $itemData["allowedBufferSize"] = $this->metadataAdapter->getAllowedBufferSize($user, $item);
                    $itemData["allowedMaxFrequency"] = $this->metadataAdapter->getAllowedMaxItemFrequency($user, $item);
                    array_push($itemsData, $itemData);
                }
                
                $response = MetadataProviderProtocol::writeGetUserItemData($itemsData);
                break;
            
            case "NUM":
                $userMessageData = MetadataProviderProtocol::readNotifyUserMessage($data);
                $this->metadataAdapter->notifyUserMessage($userMessageData["user"], $userMessageData["session_id"], $userMessageData["message"]);
                $response = MetadataProviderProtocol::writeNotifyUserMessage();
                break;
            
            case "NNT":
                $newTablesData = MetadataProviderProtocol::readNotifyNewTables($data);
                $this->metadataAdapter->notifyNewTables($newTablesData["user"], $newTablesData["session_id"], $newTablesData["tableInfos"]);
                $response = MetadataProviderProtocol::writeNotifyNewTablesData();
                break;
            
            case "NTC":
                $notifyTablesCloseData = MetadataProviderProtocol::readNotifyTablesClose($data);
                $session_id = decodeString($data[1]);
                $this->metadataAdapter->notifyTablesClose($notifyTablesCloseData["session_id"], $notifyTablesCloseData["tableInfos"]);
                $response = MetadataProviderProtocol::writeNotifyTablesClose();
                break;
        }
        
        if ($response) {
            $replyString = "$requestId|$response\n";
            $this->sendReply($replyString);
        }
    }
}

?>