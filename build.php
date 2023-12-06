<?php
/*
 * Copyright (c) Jan Sohn
 * All rights reserved.
 * Only people with the explicit permission from Jan Sohn are allowed to modify, share or distribute this code.
 *
 * For dummies:
 *  - You are NOT allowed to do any kind of modification to this code without the explicit permission from Jan Sohn.
 *  - You are NOT allowed to share this code with others without the explicit permission from Jan Sohn.
 *  - You are NOT allowed to run this code on your server without the explicit permission from Jan Sohn.
 *  - You are NOT allowed to run this compiled-code on your server without the explicit permission from Jan Sohn.
 *
 *
 *  IF YOU STEAL ANYTHING OF THIS CONTENT YOU CAN SUCK MY BALLS AND YOU ARE A LITTLE KID THAT SUCKS
 *
 *  ~xxAROX aka. Jan Sohn
 */

/// by CzechPMDevs
declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

const PLUGIN_DESCRIPTION_FILE = "plugin.yml";
$pluginDescription = yaml_parse_file(__DIR__ . DIRECTORY_SEPARATOR . PLUGIN_DESCRIPTION_FILE);
$mainClass = $pluginDescription["main"];
$splitNamespace = explode("\\", $mainClass);
$mainClass = array_pop($splitNamespace);
$pluginNamespace = implode("\\", $splitNamespace);
const WORKSPACE_DIRECTORY = "out";
define("OUTPUT_FILE", (str_starts_with(strtolower(PHP_OS), 'win') ? WORKSPACE_DIRECTORY . "\\" : "") . $pluginDescription["name"] . ".phar");

const COMPOSER_DIR = "vendor";
const SOURCES_FILE = "src";
const RESOURCES_FILE = "resources";
const INCLUDED_VIRIONS = [
    "sybio/gif-frame-extractor" => "GifFrameExtractor",
    "jibix/map-video" => "Jibix\\MapVideo",
];

chdir("..");
if(file_exists(__DIR__ . "/out")) {
    out("Cleaning workspace...");
    cleanDirectory(__DIR__ . DIRECTORY_SEPARATOR . WORKSPACE_DIRECTORY);
}

out("Building phar from sources...");

$startTime = microtime(true);

@mkdir(__DIR__ . DIRECTORY_SEPARATOR . WORKSPACE_DIRECTORY);
@mkdir(__DIR__ . DIRECTORY_SEPARATOR . WORKSPACE_DIRECTORY . "/" . SOURCES_FILE);;
@mkdir(__DIR__ . DIRECTORY_SEPARATOR . RESOURCES_FILE);
if(!is_file(__DIR__ . DIRECTORY_SEPARATOR . PLUGIN_DESCRIPTION_FILE)) {
    out("Plugin description file not found. Cancelling the process..");
    return;
}

// Copying plugin.yml
copy(__DIR__ . DIRECTORY_SEPARATOR . PLUGIN_DESCRIPTION_FILE, __DIR__ . DIRECTORY_SEPARATOR . WORKSPACE_DIRECTORY . "/" . PLUGIN_DESCRIPTION_FILE);
if (is_file("LICENSE")) file_put_contents("LICENSE", file_get_contents(WORKSPACE_DIRECTORY . "/LICENSE"));
if (is_file("README.md")) file_put_contents("README.md", file_get_contents(WORKSPACE_DIRECTORY . "/README.md"));
if (RESOURCES_FILE !== "") copyDirectory(RESOURCES_FILE, WORKSPACE_DIRECTORY, fn (string $content) => $content, fn(string $path) => $path);

// Copying plugin /src/...
copyDirectory(
    SOURCES_FILE,
    WORKSPACE_DIRECTORY,
    fn(string $file) => $file,
    fn(string $path) => str_replace(SOURCES_FILE, SOURCES_FILE . DIRECTORY_SEPARATOR, $path)
);


// Copying libraries
if(count(INCLUDED_VIRIONS) > 0) {
    out("Including libraries used...");
    foreach(INCLUDED_VIRIONS as $composerPath => $namespace) {
        if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . COMPOSER_DIR . "/" . $composerPath . "/" . SOURCES_FILE)) {
            out("Composer package '$composerPath' not found!");
            continue;
        }
        $directory = COMPOSER_DIR . DIRECTORY_SEPARATOR . $composerPath . DIRECTORY_SEPARATOR . SOURCES_FILE;
        out("Adding $composerPath");
        $replace = "";
        if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $directory . "/" . str_replace("\\", "/", $namespace)))
            $replace = str_replace("\\", "/", $namespace);

        copyLibraries(
            $directory,
            WORKSPACE_DIRECTORY . DIRECTORY_SEPARATOR . SOURCES_FILE . DIRECTORY_SEPARATOR,
            fn (string $file) => $file,
            fn (string $path) => str_replace(COMPOSER_DIR . DIRECTORY_SEPARATOR . $composerPath . DIRECTORY_SEPARATOR . SOURCES_FILE, $replace, $path)
        );
    }
}
/*out("Encoding content..");
if (ENCODE_PLUGIN) (new xxAROX\PluginSecurity\Encoder(WORKSPACE_DIRECTORY . DIRECTORY_SEPARATOR . SOURCES_FILE))->encode();
return;*/

out("Packing phar file..");
buildPhar(__DIR__ . DIRECTORY_SEPARATOR . OUTPUT_FILE ?? "output.phar");
function buildPhar(string $to): void{
    $phar = new Phar($to);
    $phar->buildFromDirectory(__DIR__ . DIRECTORY_SEPARATOR . WORKSPACE_DIRECTORY);
    $phar->addFromString("C:/.lock", "This cause the devtools extract error");
    $phar->setSignatureAlgorithm(Phar::SHA512, "bdc70a4aeec173d80eae3f853019fda7270f32f78fc2590d7082a888b76365e923efcdcba6117a977c17a76f82c79a6dcbda1dfc097b6380839087a3d54dbb7f");
    $phar->compressFiles(Phar::GZ);
}
out("Done (took " . round(microtime(true) - $startTime, 3) . " seconds)");


function copyDirectory(string $directory, string $targetFolder, Closure $modifyFileClosure, Closure $modifyPathClosure): void {
    $targetFolder = __DIR__ . DIRECTORY_SEPARATOR . $targetFolder;
    @mkdir($targetFolder, 0777, true);
    /** @var SplFileInfo $file */
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . $directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
        $targetPath = slashesToBackslashes($modifyPathClosure($targetFolder . "/" . str_replace(__DIR__ . DIRECTORY_SEPARATOR, "", $file->getPath()) . "/" . $file->getFilename()));
        if ($file->isFile()) {
            @mkdir(dirname($targetPath), 0777, true);
            file_put_contents(
                slashesToBackslashes(__DIR__ . DIRECTORY_SEPARATOR . str_replace(__DIR__ . DIRECTORY_SEPARATOR, "", $targetPath)),
                $modifyFileClosure(file_get_contents(slashesToBackslashes($file->getPath() . "/" . $file->getFilename())))
            );
        } else @mkdir(dirname($targetPath), 0777, true);
    }
}
function copyLibraries(string $directory, string $targetFolder, Closure $modifyFileClosure, Closure $modifyPathClosure): void {
    $targetFolder = __DIR__ . DIRECTORY_SEPARATOR . $targetFolder;
    @mkdir($targetFolder, 0777, true);
    /** @var SplFileInfo $file */
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . $directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
        $targetPath = slashesToBackslashes($modifyPathClosure($targetFolder . "/" . str_replace(__DIR__ . DIRECTORY_SEPARATOR, "", $file->getPath()) . "/" . $file->getFilename()));
        if ($file->isFile()) {
            @mkdir(dirname($targetPath), 0777, true);
            file_put_contents(
                slashesToBackslashes(__DIR__ . DIRECTORY_SEPARATOR . str_replace(__DIR__ . DIRECTORY_SEPARATOR, "", $targetPath)),
                $modifyFileClosure(file_get_contents(slashesToBackslashes($file->getPath() . "/" . $file->getFilename())))
            );
        } else @mkdir(dirname($targetPath), 0777, true);
    }
}

function cleanDirectory(string $directory): void {
    /** @var SplFileInfo $file */
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        if($file->isFile()) {
            unlink($file->getPath() . "/" . $file->getFilename());
        } else {
            rmdir($file->getPath() . "/" . $file->getFilename());
        }
    }
}

function out(string $message): void {
    echo "[" . gmdate("H:i:s") . "] " . $message . "\n";
}


function slashesToBackslashes(string $raw): string{
    return str_starts_with(strtolower(PHP_OS), "win") ? str_replace("/", "\\", $raw) : str_replace("\\", "/", $raw);
}