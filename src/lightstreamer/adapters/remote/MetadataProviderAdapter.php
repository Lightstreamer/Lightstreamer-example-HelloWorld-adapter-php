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

abstract class MetadataProviderAdapter extends \Stackable implements IMetaDataProvider
{

    public function notifyUser($user, $password, $httpHeaders, $clientPrincipal)
    {}

    public function getAllowedMaxBandwidth($user)
    {
        return 0.0;
    }

    public function getAllowedMaxItemFrequency($user, $item)
    {
        return 0.0;
    }

    public function getAllowedBufferSize($user, $item)
    {
        return 0;
    }

    public function isModeAllowed($user, $item, $mode)
    {
        return true;
    }

    public function modeMayBeAllowed($item, $mode)
    {
        return true;
    }

    public function notifyNewSession($user, $sessionID, $clientContext)
    {}

    public function notifyNewTables($user, $sessionID, $tables)
    {}

    public function notifySessionClose($sessionID)
    {}

    public function notifyTablesClose($sessionID, $tables)
    {}

    public function getMinSourceFrequency($item)
    {
        return 0.0;
    }

    public function getDistinctSnapshotLength($item)
    {
        return 0;
    }

    public function notifyUserMessage($user, $sessionID, $message)
    {}

    public function wantsTablesNotification($user)
    {
        return FALSE;
    }
    
    public function notifyMpnDeviceAccess($user, $sessionID, MpnDeviceInfo $device) {
        
    }
    
    public function notifyMpnDeviceTokenChange($user, $sessionID, MpnDeviceInfo $device, $newDeviceToken) {
        
    }
    
    public function notifyMpnSubscriptionActivation($user, $sessionID, TableInfo $table, MpnSubscriptionInfo $mpnSubscription) {
        
    }
    
}
?>