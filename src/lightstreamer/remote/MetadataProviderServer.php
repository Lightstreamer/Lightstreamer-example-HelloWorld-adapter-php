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

class MetaDataProviderServer extends Server
{

    private $metadataAdapter;

    public function __construct(IMetaDataProvider $metadataAdapter)
    {
        $this->metadataAdapter = $metadataAdapter;
        $this->setReplyHandler(new DefaultReplyHandler());
    }

    public function onMPI($data)
    {
        $params = MetadataProviderProtocol::readInit($data);
        try {
            $this->metadataAdapter->init($params);
            $response = MetadataProviderProtocol::writeInit();
        } catch (MetadataProviderException $mpe) {
            $response = MetadataProviderProtocol::writeInitWithException($mpe);
        }
        
        return $response;
    }

    public function onNUS($data)
    {
        $userSessionData = MetadataProviderProtocol::readNotifyUserSession($data);
        $user = $userSessionData["user"];
        $password = $userSessionData["password"];
        $httpHeaders = $userSessionData["httpHeaders"];
        $response = "";
        try {
            $this->metadataAdapter->notifyUser($user, $password, $httpHeaders, "");
            $allowedMaxBandwidth = $this->metadataAdapter->getAllowedMaxBandwidth($user);
            $wantsTablesNotification = $this->metadataAdapter->wantsTablesNotification($user);
            $response = MetadataProviderProtocol::writeNotiyUserSession("NUS", $allowedMaxBandwidth, $wantsTablesNotification);
        } catch (CreditsException $me) {} catch (AccessException $me) {}
        
        if (isset($me)) {
            $response = MetadataProviderProtocol::writeNotiyUserSessionWithException("NUS", $me);
        }
        
        return $response;
    }

    public function onNUA($data)
    {
        $userSessionData = MetadataProviderProtocol::readNotifyUserAuthorization($data);
        $user = $userSessionData["user"];
        $password = $userSessionData["password"];
        $clientPrincipal = $userSessionData["clientPrincipal"];
        $httpHeaders = $userSessionData["httpHeaders"];
        $response = "";
        try {
            $this->metadataAdapter->notifyUser($user, $password, $httpHeaders, $clientPrincipal);
            
            $allowedMaxBandwidth = $this->metadataAdapter->getAllowedMaxBandwidth($user);
            $wantsTablesNotification = $this->metadataAdapter->wantsTablesNotification($user);
            $response = MetadataProviderProtocol::writeNotiyUserSession("NUA", $allowedMaxBandwidth, $wantsTablesNotification);
        } catch (CreditsException $me) {} catch (AccessException $me) {}
        
        if (isset($me)) {
            $response = MetadataProviderProtocol::writeNotiyUserSessionWithException("NUA", $me);
        }
        
        return $response;
    }

    public function onNNS($data)
    {
        $newSessionData = MetadataProviderProtocol::readNotifyNewSession($data);
        $response = "";
        try {
            $this->metadataAdapter->notifyNewSession($newSessionData["user"], $newSessionData["session_id"], $newSessionData["clientContext"]);
            $response = MetadataProviderProtocol::writeNotifyNewSession();
        } catch (NotificationException $me) {} catch (CreditsException $me) {}
        
        if (isset($me)) {
            $response = MetadataProviderProtocol::writeNotifyNewSessionWithException($me);
        }
        
        return $response;
    }

    public function onNSC($data)
    {
        $session_id = MetadataProviderProtocol::readNotifiySessionClose($data);
        try {
            $this->metadataAdapter->notifySessionClose($session_id);
            $response = MetadataProviderProtocol::writeNotifySessionClose();
        } catch (NotificationException $ne) {
            $response = MetadataProviderProtocol::writeNotifySessionCloseWithException($ne);
        }
        
        return $response;
    }

    public function onGIS($data)
    {
        $itemsData = MetadataProviderProtocol::readGetItems($data);
        try {
            $items = $this->metadataAdapter->getItems($itemsData["user"], $itemsData["session_id"], $itemsData["group"]);
            if (is_null($items)) {
                $items = array();
            }
            $response = MetadataProviderProtocol::writeGetItems($items);
        } catch (ItemsException $ie) {
            $response = MetadataProviderProtocol::writeGetItemsWithException($ie);
        }
        
        return $response;
    }

    public function onGSC($data)
    {
        $schemaData = MetadataProviderProtocol::readGetSchema($data);
        $response = "";
        try {
            $fields = $this->metadataAdapter->getSchema($schemaData["user"], $schemaData["session_id"], $schemaData["group"], $schemaData["schema"]);
            $response = MetadataProviderProtocol::writeGetSchema($fields);
        } catch (ItemsException $e) {} catch (SchemaException $e) {}
        
        if (isset($e)) {
            $response = MetadataProviderProtocol::writeGetSchemaWithException($e);
        }
        
        return $response;
    }

    public function onGIT($data)
    {
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
        return $response;
    }

    public function onGUI($data)
    {
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
        return $response;
    }

    public function onNUM($data)
    {
        $userMessageData = MetadataProviderProtocol::readNotifyUserMessage($data);
        $this->metadataAdapter->notifyUserMessage($userMessageData["user"], $userMessageData["session_id"], $userMessageData["message"]);
        $response = MetadataProviderProtocol::writeNotifyUserMessage();
        return $response;
    }

    public function onNNT($data)
    {
        $newTablesData = MetadataProviderProtocol::readNotifyNewTables($data);
        $this->metadataAdapter->notifyNewTables($newTablesData["user"], $newTablesData["session_id"], $newTablesData["tableInfos"]);
        $response = MetadataProviderProtocol::writeNotifyNewTablesData();
        return $response;
    }

    public function onNTC($data)
    {
        $notifyTablesCloseData = MetadataProviderProtocol::readNotifyTablesClose($data);
        $this->metadataAdapter->notifyTablesClose($notifyTablesCloseData["session_id"], $notifyTablesCloseData["tableInfos"]);
        $response = MetadataProviderProtocol::writeNotifyTablesClose();
        return $response;
    }

    public function onMDA($data)
    {
        $values = MetadataProviderProtocol::readNotifyDeviceAccess($data);
        $this->metadataAdapter->notifyMpnDeviceAccess($values["user"], $values["mpnDeviceInfo"]);
        $response = MetadataProviderProtocol::writeNotifyTablesClose();
        return $response;
    }

    public function onMSA($data)
    {
        $values = MetadataProviderProtocol::readNotifySubscriptionActivation($data);
        $this->metadataAdapter->notifyMpnSubscriptionActivation($values["user"], $values["session_id"], $values["table"], $values["subscription"]);
        $response = MetadataProviderProtocol::writeNotifySubscriptionActivation();
        return $response;
    }

    public function onMDC($data)
    {
        $values = MetadataProviderProtocol::readNotifyDeviceTokenChange($data);
        $this->metadataAdapter->notifyMpnDeviceTokenChange($values["user"], $values["mpnDeviceInfo"], $values["newDeviceToken"]);
        $response = MetadataProviderProtocol::writeNotifyDeviceTokenChange();
        return $response;
    }

    public function onReceivedRequest($request)
    {
        $parsed_request = RemoteProtocol::parse_request($request);
        
        $requestId = $parsed_request["id"];
        $method = $parsed_request["method"];
        $data = $parsed_request["data"];
        
        $onFunction = "on$method";
        $response = $this->$onFunction($data);
        
        if (isset($response)) {
            $replyString = "$requestId|$response\n";
            $this->sendReply($replyString);
        }
    }
}

?>