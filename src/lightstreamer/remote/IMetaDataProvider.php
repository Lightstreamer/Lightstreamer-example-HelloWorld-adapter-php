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

interface IMetaDataProvider
{

    public function getAllowedBufferSize($user, $item);

    public function getAllowedMaxBandwidth($user);

    public function getAllowedMaxItemFrequency($user, $item);

    public function getDistinctSnapshotLength($item);

    public function getItems($user, $sessionID, $group);

    public function getMinSourceFrequency($item);

    public function getSchema($user, $sessionID, $group, $schema);

    public function init($params);

    public function isModeAllowed($user, $item, $mode);

    public function modeMayBeAllowed($item, $mode);

    public function notifyNewSession($user, $sessionID, $clientContext);

    public function notifyNewTables($user, $sessionID, $tables);

    public function notifySessionClose($sessionID);

    public function notifyTablesClose($sessionID, $tables);

    public function notifyUser($user, $password, $httpHeaders, $clientPrincipal);

    public function notifyUserMessage($user, $sessionID, $message);

    public function wantsTablesNotification($user);
}
?>