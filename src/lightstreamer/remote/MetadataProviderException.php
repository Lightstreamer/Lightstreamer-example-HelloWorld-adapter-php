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
 * Thrown by the init method in MetadataProvider if there is some problem that prevents the correct
 * behavior of the Metadata Adapter.
 * If this exception occurs, Lightstreamer Kernel must give up the startup.
 */
class MetadataProviderException extends MetadataException
{

    /**
     * Constructs a MetadataProviderException with a supplied error message text.
     *
     * @param
     *            msg The detail message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
?>