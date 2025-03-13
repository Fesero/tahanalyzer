<?php

namespace Fesero\Tahanalyzer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {

    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'handleEvent',
            'post-update-cmd' => 'handleEvent'
        ];
    }

    public static function handleEvent(Event $event)
    {
        // Автоматический запуск при установке/обновлении
        Installer::copyConfig();
    }
}