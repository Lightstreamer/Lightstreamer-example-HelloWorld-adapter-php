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

class DataProviderProtocol extends RemoteProtocol
{

    static function writeInit()
    {
        $response = "DPI|V";
        return $response;
    }

    static function readSub($data)
    {
        return self::decodeString($data[1]);
    }

    static function writeSub()
    {
        $response = "SUB|V";
        return $response;
    }

    static function readUnsub($data)
    {
        return self::decodeString($data[1]);
    }

    static function writeUnsub()
    {
        $response = "USB|V";
        return $response;
    }

    static function writeEOS($item, $requestId)
    {
        $response = "EOS|S|$item|S|$requestId";
        return $response;
    }

    static function writeCLS($item, $requestId)
    {
        $response = "CLS|S|$item|S|$requestId";
        return $response;
    }

    static function writeFailure(Exception $exception)
    {
        $message = self::encodeString($exception->getMessage());
        $response = "FAL|E|$message";
        return $response;
    }
}
?>