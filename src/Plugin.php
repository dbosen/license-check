<?php
namespace Dbosen\LicenseCheck;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\ProcessExecutor;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /** @var IOInterface */
    protected $io;

    /** @var Composer */
    protected $composer;

    /** @var ProcessExecutor */
    protected $process;

    protected static $acceptedLicenses = [
        'GPL-2.0-only' => TRUE,
        'GPL-2.0-or-later' => TRUE,
        'LGPL-2.1-only' => TRUE,
        'MIT' => TRUE,
        'X11' => TRUE,
        'BSD-2-Clause-FreeBSD' => TRUE,
        'BSD-3-Clause' => TRUE,
        'BSD-3-Clause-Clear' => TRUE,
        'CC0-1.0' => TRUE,
        'WTFPL' => TRUE,
        'Unlicense' => TRUE,
    ];

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->process = new ProcessExecutor($io);
    }

    public static function getSubscribedEvents(): array
    {
        return array(
          ScriptEvents::POST_UPDATE_CMD => 'postCmd',
          ScriptEvents::POST_INSTALL_CMD => 'postCmd',
        );
    }

    public function postCmd(Event $event)
    {
        $notAcceptedDependencies = [];
        $this->process->execute('composer licenses --format=json', $output);
        $output = json_decode($output, true);

        foreach ($output['dependencies'] as $dependency => $info) {
            foreach ($info['license'] as $license) {
                if (empty(self::$acceptedLicenses[$license])) {
                    $notAcceptedDependencies[$dependency][] = $license;
                }
            }
        }

        if (!empty($notAcceptedDependencies)) {
            print_r($notAcceptedDependencies);
            die('not accepted');
        }
    }

}
