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

class DataProviderServer extends Server implements ItemEventListener
{

    private $dataAdapter;

    public function __construct(IDataProvider $dataAdapter)
    {
        $this->dataAdapter = $dataAdapter;
        $this->setReplyHandler(new DefaultReplyHandler());
        $this->setNotifyHandler(new DefaultNotifyHandler());
    }

    protected function putActiveItem($requestId, $itemName)
    {
        $this[$itemName] = $requestId;
    }

    protected function getActiveItem($itemName)
    {
        if (isset($this[$itemName])) {
            return $this[$itemName];
        } else {
            echo "No active item found for request [$itemName]!\n";
            return NULL;
        }
    }

    protected function removeActiveItem($itemName)
    {
        if (isset($this[$itemName])) {
            unset($this[$itemName]);
        } else {
            echo "No active item found for [$itemName]!\n";
        }
    }

    protected function doNotify($data)
    {
        $timestamp = strval(round(microtime(true) * 1000));
        $notifyString = "$timestamp|$data\n";
        $this->sendNotify($notifyString);
    }

    public function update($itemName, $eventsMap, $isSnapshot)
    {
        $requestId = $this->getActiveItem($itemName);
        
        if (! is_null($requestId)) {
            $snapshotFlag = RemoteProtocol::encodeBoolean($isSnapshot);
            $qry = "UD3|S|$itemName|S|$requestId|B|$snapshotFlag";
            foreach ($eventsMap as $field_name => $field_value) {
                $enc_field_name = RemoteProtocol::encodeString($field_name);
                $enc_field_value = RemoteProtocol::encodeString($field_value);
                $qry .= "|S|$enc_field_name|S|$enc_field_value";
            }
            $this->doNotify($qry);
        } else {
            echo "Unexpected update for item [$itemName]!\n";
        }
    }

    public function clearSnapshot($itemName)
    {
        $requestId = $this->getActiveItem($itemName);
        
        if (! is_null($requestId)) {
            $notify = DataProviderProtocol::writeEOS($itemName, $requestId);
            $this->doNotify($notify);
        } else {
            echo "Unexpected clearSnapshot for item [$itemName]!\n";
        }
    }

    public function endOfSnapshot($itemName)
    {
        $requestId = $this->getActiveItem($itemName);
        if (! is_null($requestId)) {
            $notify = DataProviderProtocol::writeCLS($itemName, $requestId);
            $this->notify($notify);
        } else {
            echo "Unexpected endOfSnapshot for item [$itemName]!\n";
        }
    }

    public function failure(\Exception $exception)
    {
        $notify = DataProviderProtocol::writeFailure($exception);
        $this->doNotify($notify);
    }

    public function onReceivedRequest($request)
    {
        $parsed_request = RemoteProtocol::parse_request($request);
        $requestId = $parsed_request["id"];
        $method = $parsed_request["method"];
        $data = $parsed_request["data"];
        
        switch ($method) {
            case "DPI":
                $params = DataProviderProtocol::readInit($data);
                $this->dataAdapter->init($params);
                $this->dataAdapter->setListener($this);
                $response = DataProviderProtocol::writeInit();
                break;
            
            case "SUB":
                $itemName = DataProviderProtocol::readSub($data);
                $this->putActiveItem($requestId, $itemName);
                $snapshotAvailable = $this->dataAdapter->isSnapshotAvailable($itemName);
                
                if (! $snapshotAvailable) {
                    $this->endOfSnapshot($itemName, $requestId);
                }
                
                $this->dataAdapter->subscribe($itemName);
                $response = DataProviderProtocol::writeSub();
                break;
            
            case "USB":
                $itemName = DataProviderProtocol::readUnsub($data);
                $this->dataAdapter->unsubscribe($itemName);
                $this->removeActiveItem($itemName);
                $response = DataProviderProtocol::writeUnsub();
                break;
        }
        
        if ($response) {
            $replyString = "$requestId|$response\n";
            $this->sendReply($replyString);
        }
    }
}

?>