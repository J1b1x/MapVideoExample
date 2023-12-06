<?php
namespace Jibix\MapVideoExample\command;
use Jibix\MapVideo\session\VideoSession;
use Jibix\MapVideo\video\Video;
use Jibix\MapVideo\video\VideoManager;
use Jibix\MapVideo\video\VideoPlaySettings;
use Jibix\MapVideoExample\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\Server;


/**
 * Class VideoCommand
 * @package Jibix\MapVideoExample\command
 * @author Jibix
 * @date 06.12.2023 - 19:06
 * @project MapVideo
 */
class VideoCommand extends Command{

    public function __construct(string $name){
        parent::__construct($name, "Play a video on a map", "/$name <name>");
        $this->setPermission("video.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command must be executed as a player!");
            return;
        }
        if (!$args) throw new InvalidCommandSyntaxException();
        $video = array_shift($args);
        if (!is_file($file = Main::getInstance()->getDataFolder() . "$video.gif")) {
            $sender->sendMessage("§cThis video could not be found!");
            return;
        }
        $name = $sender->getName();
        VideoManager::getInstance()->loadVideo(
            Video::id($video),
            $file,
            static function (Video $video) use ($name): void{
                $player = Server::getInstance()->getPlayerExact($name);
                if ($player !== null) {
                    $player->sendActionBarMessage("§aDone, starting video...");
                    VideoSession::get($player)->play($video, new VideoPlaySettings());
                }
            },
            static function (int $totalFrames, int $loadedFrames) use ($name): void{
                $player = Server::getInstance()->getPlayerExact($name);
                $player?->sendActionBarMessage("§bLoaded frame §a{$loadedFrames}§7/§c{$totalFrames} §7(§6" . round($loadedFrames / $totalFrames * 100) . "%§7)");
            }
        );
    }
}