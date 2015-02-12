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

abstract class Server extends \Thread
{

    protected $rrHandle;

    protected $nHandle;
    
    protected $replyHandler;

    protected $notifyHandler;
    
    public abstract function onReceivedRequest($request);

    public function setRequestReplyHandle($requestReplyhandle)
    {
        $this->rrHandle = $requestReplyhandle;
    }

    public function setNotifyHandle($notifyHandle)
    {
        $this->nHandle = $notifyHandle;
    }
    
    public function setReplyHandler($replyHandler) {
        $this->replyHandler = $replyHandler;
    }
    
    public function setNotifyHandler($notifyHandler) {
        $this->notifyHandler = $notifyHandler;
    }

    public final function sendReply($reply)
    {
       $this->replyHandler->sendReply($this->rrHandle, $reply);
    }

    public final function sendNotify($notify)
    {
        if (! is_null($this->nHandle)) {
            $this->notifyHandler->sendNotify($this->nHandle, $notify);
        }
    }

    public function run()
    {
        while (! feof($this->rrHandle)) {
            $request = fgets($this->rrHandle);
            if ($request) {
                $this->onReceivedRequest($request);
            } else {
                echo "No request received!";
            }
        }
    }
}
?>