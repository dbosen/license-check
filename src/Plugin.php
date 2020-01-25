<?php
namespace Dbosen\LicenseCheck;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for testing licenses of required dependencies.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The Input/Output helper.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * The composer object.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * The accepted licences.
     *
     * @var array
     */
    protected static $acceptedLicenses = [
        'GPL-2.0-only' => true,
        'GPL-2.0-or-later' => true,
        'LGPL-2.1-only' => true,
        'MIT' => true,
        'X11' => true,
        'BSD-2-Clause-FreeBSD' => true,
        'BSD-3-Clause' => true,
        'BSD-3-Clause-Clear' => true,
        'CC0-1.0' => true,
        'WTFPL' => true,
        'Unlicense' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
          ScriptEvents::POST_UPDATE_CMD => 'postCmd',
          ScriptEvents::POST_INSTALL_CMD => 'postCmd',
        );
    }

    /**
     * Handling post update/install events.
     *
     * @param Event $event
     *   The event.
     *
     * @return void
     */
    public function postCmd(Event $event)
    {
        $notAcceptedDependencies = [];
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getPackages();

        foreach ($packages as $package) {
            foreach ($package->getLicense() as $license) {
                if (empty(self::$acceptedLicenses[$license])) {
                    $notAcceptedDependencies[$package->getName()][] = $license;
                }
            }
        }

        if (!empty($notAcceptedDependencies)) {
            $this->io->write(print_r($notAcceptedDependencies, true));
            die('not accepted');
        }
    }

}
