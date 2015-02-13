<?php
namespace lightstreamer\remote;

interface INotifyHandler {
    
    function sendNotify($handle, $reply);
}
?>