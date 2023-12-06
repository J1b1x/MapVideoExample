<?php
namespace Jibix\MapVideoExample;
use Jibix\MapVideo\MapVideo;
use Jibix\MapVideoExample\command\VideoCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;


/**
 * Class Main
 * @package Jibix\MapVideoExample
 * @author Jibix
 * @date 06.12.2023 - 19:05
 * @project MapVideo
 */
final class Main extends PluginBase{
    use SingletonTrait{
        setInstance as private;
        reset as private;
    }

    protected function onLoad(): void{
        self::setInstance($this);
    }

    protected function onEnable(): void{
        MapVideo::initialize($this);
        $this->getServer()->getCommandMap()->register($this->getName(), new VideoCommand("video"));
    }
}