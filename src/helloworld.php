<?php
/*
 * Copyright (c) Lightstreamer Srl
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
require "autoload.php";

use Lightstreamer\adapters\remote\metadata\LiteralBasedProvider;
use Lightstreamer\adapters\remote\MetaDataProviderServer;
use Lightstreamer\adapters\remote\DataProviderServer;
use Lightstreamer\adapters\remote\IDataProvider;
use Lightstreamer\adapters\remote\ItemEventListener;
use Lightstreamer\adapters\remote\Server;

class GreetingsThread extends Thread
{

    private $listener;

    private $continue;

    private $loop = true;

    private $paused = true;

    private $itemName;

    /*
     * Terminate the Thread when application stops.
     */
    public function end()
    {
        $this->synchronized(function ($thread)
        {
            $thread->loop = false;
            if ($thread->isWaiting()) {
                $thread->notify();
            }
        }, $this);
        $this->join();
    }

    /*
     * Pause the thread is paused, no events generation from this moment.
     */
    public function pause()
    {
        $this->synchronized(function ($thread)
        {
            $thread->paused = true;
        }, $this);
    }

    /*
     * Resume the Thread to geneate new events.
     */
    public function resume($itemName)
    {
        return $this->synchronized(function ($thread, $itemName)
        {
            $thread->paused = false;
            $thread->itemName = $itemName;
            if ($thread->isWaiting()) {
                $thread->notify();
            }
        }, $this, $itemName);
    }

    /*
     * Set the ItemEventListener for events updating
     */
    public function setListener(ItemEventListener $listener)
    {
        $this->listener = $listener;
    }

    public function run()
    {
        $c = 0;
        while ($this->loop) {
            $this->synchronized(function ($thread)
            {
                if ($thread->paused) {
                    echo "Events generation paused ...\n";
                    $thread->wait();
                    echo "Resuming generating events on {$thread->itemName}...\n";
                }
            }, $this);
            
            /* Prepare the events map */
            $eventsMap = array(
                "message" => $c % 2 == 0 ? "Hello" : "World",
                "timestamp" => date("H:i:s Y:m:d")
            );
            $c ++;
            usleep(rand(0, 2000000));
            $this->listener->update($this->itemName, $eventsMap, FALSE);
        }
    }
}

class HelloWorldDataAdapter implements IDataProvider
{

    private $greetings;

    public function __construct(GreetingsThread $greetings)
    {
        $this->greetings = $greetings;
    }

    public function init($params)
    {}

    public function subscribe($itemName)
    {
        if ($itemName = "greetings") {
            $this->greetings->resume($itemName);
        }
    }

    public function unsubscribe($item)
    {
        if ($item = "greetings") {
            $this->greetings->pause();
        }
    }

    public function isSnapshotAvailable($item)
    {
        return false;
    }

    public function setListener(ItemEventListener $listener)
    {
        $this->greetings->setListener($listener);
    }
}

class StarterServer
{

    private $rrPort;

    private $notifyPort;

    private $server;

    public function __construct($host, $rrPort, $notifyPort = null)
    {
        $this->host = $host;
        $this->rrPort = $rrPort;
        $this->notifyPort = $notifyPort;
    }

    public function start(Server $server)
    {
        $this->server = $server;
        $canStart = true;
        if ($rrSocket = stream_socket_client("tcp://{$this->host}:{$this->rrPort}", $errno, $errstr, 5)) {
            $this->server->setRequestReplyHandle($rrSocket);
            
            if (! is_null($this->notifyPort)) {
                if ($notify = stream_socket_client("tcp://{$this->host}:{$this->notifyPort}", $errno, $errstr, 5)) {
                    $this->server->setNotifyHandle($notify);
                } else {
                    $canStart = false;
                }
            }
        } else {
            $canStart = false;
        }
        
        if ($canStart) {
            $this->server->start();
        } else {
            echo "Connection error= [$errno]:[$errstr]\n";
        }
    }
}

try {
    $host = "localhost";
    $data_rrport = 6661;
    $data_notifport = 6662;
    
    /* Starting the GreetingsThread in the current context */
    $greetings = new GreetingsThread();
    $greetings->start();
    
    $data_adapter = new HelloWorldDataAdapter($greetings);
    
    $dataprovider_server = new DataProviderServer($data_adapter);
    
    /* Starting the StarterServer */
    $dataproviderServerStarter = new StarterServer($host, $data_rrport, $data_notifport);
    $dataproviderServerStarter->start($dataprovider_server);
} catch (Exception $e) {
    echo "Caught exception {$e->getMessage()}\n";
}
?>