# Lightstreamer - "Hello World" Tutorial - PHP Adapter #
<!-- START DESCRIPTION lightstreamer-example-helloworld-adapter-php -->

The "Hello World" Tutorial is a very basic example, based on Lightstreamer, where we push the alternated strings "Hello" and "World", followed by the current timestamp, from the server to the browser.

This project, of the "Hello World with Lightstreamer" series, will focus on a [PHP](http://www.php.net/) port of the Java Adapter illustrated in [Lightstreamer - "Hello World" Tutorial - Java Adapter](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-java). In particular, a PHP-CLI version of the Data Adapter will be shown.

As an example of [Clients Using This Adapter](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-php#clients-using-this-adapter), you may refer to the [Lightstreamer - "Hello World" Tutorial - HTML Client](https://github.com/Weswit/Lightstreamer-example-HelloWorld-client-javascript) and view the corresponding [Live Demo](http://demos.lightstreamer.com/HelloWorld/).

## Detail

First, please take a look at the previous installment [Lightstreamer - "Hello World" Tutorial - HTML Client](https://github.com/Weswit/Lightstreamer-example-HelloWorld-client-javascript), which provides some background and the general description of the application. Notice that the front-end will be exactly the same. We created a very simple HTML page that subscribes to the "greetings" item, using the "HELLOWORLD" Adapter. Now, we will replace the "HELLOWORLD" Adapter implementation based on Java with a PHP equivalent (running through the PHP-CLI SAPI). On the client side, nothing will change, as server-side Adapters can be transparently switched and changed, as long as they respect the same interfaces. Thanks to this decoupling, provided by Lightstreamer Server, we could even do something different. For example, we could keep the Java Adapter on the server side and use Flex, instead of HTML, on the client side. Or, we could use the PHP Adapter on the server side and use Java, instead of HMTL or Flex, on the client side. Basically, all the combinations of languages and technologies on the client side and on the server side are supported.

Please refer to [General Concepts](http://www.lightstreamer.com/docs/base/General%20Concepts.pdf) for more details about Lightstreamer Adapters.

### PHP Interfaces

Lightstreamer Server exposes native Java Adapter interfaces. The PHP interfaces are added through the ***Lightstreamer Adapter Remoting Infrastructure (ARI)***. Let's have a look at it.

![General architecture](ls-ari.png)

ARI is simply made up of two Proxy Adapters and a **Network Protocol**. The two Proxy Adapters implement the Java interfaces and are meant to be plugged into Lightstreamer Kernel, exactly as we did for our original "HELLOWORLD" Java Adapter. There are two Proxy Adapters because one implements the Data Adapter interface and the other implements the Metadata Adapter interface. Our "Hello World" example uses a default Metadata Adapter, so we only need the **Proxy Data Adapter**.

Basically, the Proxy Data Adapter exposes the Data Adapter interface through TCP sockets. In other words, it offers a Network Protocol, which any remote counterpart can implement to behave as a Lightstreamer Data Adapter. This means you can write a remote Data Adapter in any language, provided that you have access to plain TCP sockets. 
But, if your remote Data Adapter is based on certain languages/technologies (such as Java, .NET, and Node.js), you can forget about direct socket programming, and leverage a ready-made library that exposes a higher level interface. Now, you will simply have to implement this higher level interface.<br>
So the Proxy Data Adapter converts from a Java interface to TCP sockets, and the API library converts from TCP sockets to higher level interface.

You may find more details about ARI in [Adapter Remoting Infrastructure Network Protocol Specification](http://www.lightstreamer.com/docs/adapter_generic_base/ARI%20Protocol.pdf).

<!-- END DESCRIPTION lightstreamer-example-helloworld-adapter-php -->

### Dig the Code

#### The PHP Data Adapter
The code example is structured as follows:
* The `helloworld.php` file, which is the entry point of the example.
* The `autoload.php` file, which loads the required classes.
* The "lightstreamer" hierarchy directory structure, containing all the PHP classes (a file for each class), which implement the ARI Protocol.

First, we import the classes included in the lightstreamer namespace and sub-namespaces, required to the communicate with the Proxy Adapters:

```php
use Lightstreamer\adapters\remote\metadata\LiteralBasedProvider;
use Lightstreamer\adapters\remote\MetaDataProviderServer;
use Lightstreamer\adapters\remote\DataProviderServer;
use Lightstreamer\adapters\remote\IDataProvider;
use Lightstreamer\adapters\remote\ItemEventListener;
use Lightstreamer\adapters\remote\Server;
```
Then, we define a Thread to generate the "greetings" events to be send to the Proxy Adapter.
```php
class GreetingsThread extends Thread
{

    private $listener;

    private $continue;

    private $loop = true;

    private $paused = true;
    
    private $itemName;

    /*
     * Pause the Thread, no events generation from this moment.
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
```
The class also provides a couple of methods (*pause()* and *resume()*) to manage the thread's execution, according to the life-cycle of the subscribed item.

The *HelloWorldDataAdapter* implements the **IDataProvider** PHP interface, which is a PHP equivalent of the Java DataProvider interface: 

```php
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
```
The Adapter's subscribe method is invoked when a new item is subscribed for the first time. When the "greetings" item is subscribed by the first user, the GreetingsThread is resumed and then it starts to generate the real-time data. If more users subscribe to the "greetings" item, the subscribe method is no longer invoked. When the last user unsubscribes from this item, the Adapter is notified through the unsubscribe invocation. In this case, the GreetingsThread is paused and no more events are published  for that item. If a new user re-subscribes to "greetings", the subscribe method is invoked again ad the process resumes the same way.

The *StartServer* class is a simple utility, useful to configure and start a Server instances, which is the abstract class at the top of the hierarchy of the PHP classes implementing the ARI Protocol.
```php
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
```

The final part of the script initializes and activates the communication with the Proxy Adapters:
```php
try {
    $host = "localhost";
    $data_rrport = 6661;
    $data_notifport = 6662;
    
    $greetings = new GreetingsThread();
    $greetings->start();
    
    $data_adapter = new HelloWorldDataAdapter($greetings);
    $dataprovider_server = new DataProviderServer($data_adapter);
    
    $dataproviderServerStarter = new StarterServer($host, $data_rrport, $data_notifport);
    $dataproviderServerStarter->start($dataprovider_server);
} catch (Exception $e) {
    echo "Caught exception {$e->getMessage()}\n";
}
```
First, we create and start the *GreetingThread*. Then, we instantiate the *HelloWordDataAdaper*, passing the handle to the GreetingsThread. After that, we create a *DataProviderServer* instance (which is the PHP equivalent of the Java DataProviderServer and extends the Server abstract class defined above) and assign the HelloWorldAdapter instance to it.
Since the Proxy Data Adapter to which our remote PHP Adapter will connect needs two connections, we create and setup the StarterServer with two different TPC ports (6661 and 6662 as configured in the beginning ) in order to make it create two stream sockets. Finally, we start DataProviderServer.

#### The Adapter Set Configuration

This Adapter Set is configured and will be referenced by the clients as `PHP_HELLOWORLD`.
For this demo, we configure just the Data Adapter as a *Proxy Data Adapter*, while instead, as Metadata Adapter, we use the [LiteralBasedProvider](https://github.com/Weswit/Lightstreamer-example-ReusableMetadata-adapter-java), a simple full implementation of a Metadata Adapter, already provided by Lightstreamer server.
As *Proxy Data Adapter*, you may configure also the robust versions. The *Robust Proxy Data Adapter* has some recovery capabilities and avoid to terminate the Lightstreamer Server process, so it can handle the case in which a Remote Data Adapter is missing or fails, by suspending the data flow and trying to connect to a new Remote Data Adapter instance. Full details on the recovery behavior of the Robust Data Adapter are available as inline comments within the `DOCS-SDKs/adapter_remoting_infrastructure/doc/adapter_robust_conf_template/adapters.xml` file in your Lightstreamer Server installation.

The `adapters.xml` file for this demo should look like:
```xml
<?xml version="1.0"?>
 
<adapters_conf id="PHP_HELLOWORLD">
 
  <metadata_provider>
    <adapter_class>com.lightstreamer.adapters.metadata.LiteralBasedProvider</adapter_class>
  </metadata_provider>
 
  <data_provider>
    <adapter_class>PROXY_FOR_REMOTE_ADAPTER</adapter_class>
    <classloader>log-enabled</classloader>
    <param name="request_reply_port">6663</param>
    <param name="notify_port">6664</param>
  </data_provider>
 
</adapters_conf>
```

<i>NOTE: not all configuration options of a Proxy Adapter are exposed by the file suggested above.<br>
You can easily expand your configurations using the generic template, `DOCS-SDKs/adapter_remoting_infrastructure/doc/adapter_conf_template/adapters.xml` or `DOCS-SDKs/adapter_remoting_infrastructure/doc/adapter_robust_conf_template/adapters.xml`, as a reference.</i>

## Install
If you want to install a version of this demo in your local Lightstreamer Server, follow these steps:
* Download *Lightstreamer Server* (Lightstreamer Server comes with a free non-expiring demo license for 20 connected users) from [Lightstreamer Download page](http://www.lightstreamer.com/download.htm), and install it, as explained in the `GETTING_STARTED.TXT` file in the installation home directory.
* Get the `deploy.zip` file installed from [releases](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-php/releases) and unzip it, obtaining the `deployment` folder.
* Plug the Proxy Data Adapter into the Server: go to the `Deployment_LS` folder and copy the `PHPHelloWorld` directory and all of its files to the `adapters` folder of your Lightstreamer Server installation.
* Alternatively, you may plug the *robust* versions of the Proxy Data Adapter: go to the `Deployment_LS(robust)` folder and copy the `PhpHelloWorld` directory and all of its files into the `adapters` folder.
* Install the PHP Remote Adapter
 * Create a directory where to deploy the PHP Remote Adapter, let's call it `Deployment_PHP_Remote_Adapter`.
 * Download all the PHP source files from this project and copy them into the `Deployment_PHP_Remote_Adapter` folder.
*  Launch Lightstreamer Server. The Server startup will complete only after a successful connection between the Proxy Data Adapter and the Remote Data Adapter.
* Launch the PHP Remote Adapter: go to the `Deployment_PHP_Remote_Adapter` folder and launch:<BR/>
`> php helloworld.php`<BR/>
* IMPORTANT: The demo requires that the [pthreads](http://php.net/manual/en/intro.pthreads.php) module is installed into your php  environment. You can get detailed information on how to properly install the module [here](http://php.net/manual/en/pthreads.setup.php). The demo has been succesfully tested on the following environments:
 * Windows 7 and 8, with PHP version [VC 11 Thread Safe for X86](http://windows.php.net/downloads/releases/php-5.6.5-Win32-VC11-x86.zip) and pthreed module version [2.0.10-5.6-ts-vc11](http://windows.php.net/downloads/pecl/releases/pthreads/2.0.10/php_pthreads-2.0.10-5.6-ts-vc11-x86.zip)
 * Ubuntu Linux version 14.10, with PHP version 5.6.5 (compiled with the *--enable-maintainer-zts* flag) and pthread module version 2.0.10, installed as a pecl extension.
* Test the Adapter, launching the ["Hello World" Tutorial - HTML Client](https://github.com/Weswit/Lightstreamer-example-HelloWorld-client-javascript)  listed in [Clients Using This Adapter](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-php#clients-using-this-adapter).
    * To make the ["Hello World" Tutorial - HTML Client](https://github.com/Weswit/Lightstreamer-example-HelloWorld-client-javascript) front-end pages get data from the newly installed Adapter Set, you need to modify the front-end pages and set the required Adapter Set name to PHP_HELLOWORLD, when creating the LightstreamerClient instance. So edit the `index.html` page of the Hello World front-end, deployed under `Lightstreamer/pages/HelloWorld`, and replace:<BR/>
`var client = new LightstreamerClient(null, "HELLOWORLD");`<BR/>
with:<BR/>
`var client = new LightstreamerClient(null, "PHP_HELLOWORLD");`<BR/>
    * Open a browser window and go to: [http://localhost:8080/HelloWorld/]()

### Clients Using This Adapter
<!-- START RELATED_ENTRIES -->

* [Lightstreamer - "Hello World" Tutorial - HTML Client](https://github.com/Weswit/Lightstreamer-example-HelloWorld-client-javascript)

<!-- END RELATED_ENTRIES -->

### Related Projects

* [Complete list of "Hello World" Adapter implementations with other technologies](https://github.com/Weswit?query=Lightstreamer-example-HelloWorld-adapter)
* [Lightstreamer - Reusable Metadata Adapters - Java Adapter](https://github.com/Weswit/Lightstreamer-example-ReusableMetadata-adapter-java)

## Lightstreamer Compatibility Notes

* Compatible with Lightstreamer SDK for Generic Adapters version 1.7 or newer.
* Compatible with Lightstreamer JavaScript Client Library version 6.0 or newer.

## Final Notes

Please [post to our support forums](http://forums.lightstreamer.com) any feedback or question you might have. Thanks!
