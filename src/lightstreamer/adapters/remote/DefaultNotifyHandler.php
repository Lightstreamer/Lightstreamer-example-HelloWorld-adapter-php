<?php
namespace lightstreamer\adapters\remote;

class DefaultNotifyHandler implements INotifyHandler
{

    public function sendNotify($handle, $reply)
    {
        RemoteProtocol::sendReply($handle, $reply);
    }
}