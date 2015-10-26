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

/**
 * Thrown by the notify* methods in MetadataProvider if some functionality cannot be allowed
 * to the supplied User. This may occur if the user is not granted some resource or if the user
 * would exceed the granted amount. Different kinds of problems can be distinguished by an error code.
 * Both the error message detail and the error code will be forwarded by Lightstreamer Kernel to the Client.
 */
class CreditsException extends MetadataException
{

    private $clientErrorMsg;
    
    public function __construct($message, $code, $clientErrorMsg = null)
    {
        parent::__construct($message, $code);
        $this->clientErrorMsg = $clientErrorMsg;
    }
    
    public function getClientUserMsg() {
        return $this->clientErrorMsg;
    }
}
?>