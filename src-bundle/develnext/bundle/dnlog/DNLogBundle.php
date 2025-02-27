<?php

namespace develnext\bundle\dnlog;

use dnlogger\DNLogServer;
use ide\bundle\AbstractJarBundle;
use ide\Ide;
use ide\library\IdeLibraryBundleResource;
use ide\Logger;
use php\desktop\Runtime;

class DNLogBundle extends AbstractJarBundle
{
    private $instance;



    public function onRegister(IdeLibraryBundleResource $resource)
    {
        parent::onRegister($resource);

        // Logger::info($resource->getPath());


        if (!class_exists(DNLogServer::class)) {
            Runtime::addJar($resource->getPath() . '\dn-dn-dnlog-ext-bundle.jar');
            require 'res://vendor/develnext.bundle.dnlog.DNLogBundle/dnlogger/DNLogServer.php';
        }


        $this->instance = new DNLog();

        Ide::get()->bind("shutdown", function () {
            $this->instance->shutdown();
            DNLogServer::stop();
        });

    }

}