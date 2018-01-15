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

class MpnSubscriptionInfo
{

    protected $data = array();

    public function __construct(MpnDeviceInfo $device, $trigger, $notificationFormat)
    
    {
        $this->data["mpnDeviceInfo"] = $device;
        $this->data["trigger"] = $trigger;
        $this->data["notificationFormat"] = $notificationFormat;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        
        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
    
    public function __toString() {
        return sprintf("\nMpnDeviceInfo=[%s]\n\tTrigger=[%s]\n\tNotificationFormat=[%s]\n", (string)$this->mpnDeviceInfo, $this->trigger, $this->notificationFormat);
    }
}
?>