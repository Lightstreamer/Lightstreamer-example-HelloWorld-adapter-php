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

/**
 * Thrown by the notifyNewSession method of MetadataProvider
 * if a User is not enabled to open a new Session but he would be enabled
 * as soon as another Session were closed.
 * By using this exception,
 * the ID of the other Session is also supplied.<BR>
 * After receiving this exception, the Server may try to close
 * the specified session and invoke notifyNewSession again.
 */
class ConflictingSessionException extends CreditsException
{

    private $conflictingSessionID;

    public function __construct($message, $code, $clientErrorMsg, $conflictingSessionID)
    {
        parent::__construct($message, $code, $clientErrorMsg);
        $this->conflictingSessionID = $conflictingSessionID;
    }

    public function getConflictingSessionID()
    {
        return $this->conflictingSessionID;
    }
}
?>