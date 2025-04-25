<?php
declare(strict_types=1);

namespace Fesero\Tahanalyzer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {

    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {

    }

    /**
     * Summary of getSubscribedEvents
     * @return array{post-install-cmd: string, post-update-cmd: string}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'post-install-cmd' => 'handleEvent',
            'post-update-cmd' => 'handleEvent'
        ];
    }

    /**
     * Summary of handleEvent
     * @param \Composer\Script\Event $event
     * @return void
     */
    public static function handleEvent(Event $event): void
    {
        // Автоматический запуск при установке/обновлении
        Installer::copyConfig();
    }
}