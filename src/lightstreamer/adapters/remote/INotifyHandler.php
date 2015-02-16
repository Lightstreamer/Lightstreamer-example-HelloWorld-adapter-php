<?php
namespace lightstreamer\adapters\remote;

interface INotifyHandler {
    
    function sendNotify($handle, $reply);
}
?>