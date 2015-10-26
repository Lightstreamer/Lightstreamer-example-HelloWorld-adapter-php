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

class MpnApnSubscriptionInfo extends MpnSubscriptionInfo
{

    public function __construct(MpnDeviceInfo $device, $trigger, $sound, $badge, $localizedActionKey, $launchImage, $format, $localizedFormatKey, $arguments, $customData)
    
    {
        parent::__construct($device, $trigger);
        $this->data["sound"] = $sound;
        $this->data["badge"] = $badge;
        $this->data["localizedActionKey"] = $localizedActionKey;
        $this->data["launchImage"] = $launchImage;
        $this->data["format"] = $format;
        $this->data["localizedFormatKey"] = $localizedFormatKey;
        $this->data["arguments"] = $arguments;
        $this->data["customData"] = $customData;
    }
    
    public function __toString() {
        return sprintf("%s\n\tSound=[%s]\n\tBadge=[%s]\n\tLocalizedActionKey=[%s]\n\tLaunchImage=[%s]\n\tFormat=[%s]\n\tLocalizedFormatKey=[%s]\n\tArguments=[%s]\n\tCustomData=[%s]\n",
            parent::__toString(), 
            $this->data["sound"],
            $this->data["badge"],
            $this->data["localizedActionKey"],
            $this->data["launchImage"],
            $this->data["format"],
            $this->data["localizedFormatKey"],
            implode("|", $this->data["arguments"]),
            implode("|", $this->data["customData"]));
    }
}
?>