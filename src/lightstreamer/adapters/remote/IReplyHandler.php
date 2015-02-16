<?php
namespace lightstreamer\adapters\remote;

interface IReplyHandler {
    
    function sendReply($handle, $reply);
}
?>