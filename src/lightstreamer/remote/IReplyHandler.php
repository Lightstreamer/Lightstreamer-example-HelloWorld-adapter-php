<?php
namespace lightstreamer\remote;

interface IReplyHandler {
    
    function sendReply($handle, $reply);
}
?>