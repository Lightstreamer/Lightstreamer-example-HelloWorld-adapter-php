<?php
/*
 * Copyright (c) Lightstreamer Srl
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace lightstreamer\adapters\remote;

/**
 * Thrown by the subscribe and unsubscribe methods in DataProvider if the method execution has caused
 * a severe problem that can compromise future operation of the Data Adapter.
 */
class FailureException extends DataException
{

    /**
     * Constructs a FailureException with a supplied error message text.
     *
     * @param
     *            msg The detail message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}