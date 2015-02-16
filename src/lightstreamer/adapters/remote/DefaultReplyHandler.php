<?php
namespace lightstreamer\adapters\remote;

class DefaultReplyHandler implements IReplyHandler
{

    public function sendReply($handle, $reply)
    {
        RemoteProtocol::sendReply($handle, $reply);
    }
}