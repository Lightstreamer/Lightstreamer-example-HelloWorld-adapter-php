<?php
/*
  Copyright (c) Lightstreamer Srl

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
namespace lightstreamer\adapters\remote\metadata;

use lightstreamer\adapters\remote\MetadataProviderAdapter;

class LiteralBasedProvider extends MetadataProviderAdapter

{

    private $data = array(
        "max_bandwidth" => 0.0,
        "max_frequency" => 0.0,
        "buffer_size" => 0,
        "distinct_snapshot_length" => 10
    );

    private $allowedUsers;

    function __construct()
    {}

    public function init($params)
    {
        if (! isset($params)) {
            $params = array();
        }
        if (array_keys($params, "allowed_users")) {
            $allowedUsers = $params["allowed_users"];
            $this->allowedUsers = explode(" ", $allowedUsers);
        }
        
        $this->data = array_merge($this->data, $params);
    }

    public function getItems($user, $sessionID, $group)
    {
        return explode(" ", $group);
    }

    public function getSchema($user, $sessionID, $group, $schema)
    {
        return explode(" ", $schema);
    }

    public function notifyUser($user, $password, $httpHeaders, $clientPrincipal)
    {
        // if (! $this->checkUser($user)) {}
    }

    private function checkUser($user)
    {
        if (! isset($this->allowedUsers)) {
            return true;
        }
        
        if (is_null($user)) {
            return false;
        }
        
        foreach ($this->allowedUsers as $allowedUser) {
            if (is_null($allowedUser)) {
                continue;
            }
            
            if ($allowedUser == $user) {
                return true;
            }
        }
        
        return false;
    }

    public function getAllowedMaxItemFrequency($user, $item)
    {
        return $this->data["max_frequency"];
    }

    public function getAllowedMaxBandwidth($user)
    {
        return $this->data["max_bandwidth"];
    }

    public function getAllowedBufferSize($user, $item)
    {
        return $this->data["buffer_size"];
    }

    public function getDistinctSnapshotLength($item)
    {
        return $this->data["distinct_snapshot_length"];
    }
}

?>