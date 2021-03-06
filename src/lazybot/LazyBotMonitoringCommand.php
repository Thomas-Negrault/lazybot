<?php
/**
 * Author: Thomas Negrault
 * Date: 05/04/15
 */
namespace lazybot;

use Goutte\Client;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;


class LazyBotMonitoringCommand extends Command
{

    /**
     * @var string $folder
     */
    protected $folder;

    /**
     * @var array $language
     */
    protected $language;

    /** @var  array $config */
    protected $config;

    /** @var  array $userConfig */
    protected $userConfig;


    protected function configure()
    {
        $this->setName('monitor');
        $this->setDescription('Monitor a folder for changes and search subtitles on Addic7ed');

        $this->addArgument('folder', InputArgument::REQUIRED);
        $this->addOption(
            'language',
            'l',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            '',
            array('french')
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->folder   = realpath($input->getArgument('folder'));
        $this->language = array_map('ucfirst', $input->getOption("language"));
        $configLoader = new FileLocator(__DIR__.'/../../app/config');
        $this->config = Yaml::parse($configLoader->locate('parameters.yml'))["parameters"];
        $this->userConfig = Yaml::parse($configLoader->locate('userConfig.yml'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded("inotify") === false) {
            $output->writeln("<error>Extension inotify missing</error>");
            $output->writeln("Check <comment>https://secure.php.net/manual/fr/book.inotify.php</comment> for more informations");
        } else {

            $download_dir = $this->folder;
            $output->writeln(sprintf("Starting monitoring folder <info>%s</info>", $this->folder));

            $allowedExtensions = isset($this->userConfig["extensions"]) ? $this->userConfig["extensions"]: $this->config["defaultExtensions"];
            $fd = inotify_init();
            $watch_descriptor = inotify_add_watch($fd, $download_dir, IN_CREATE);
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            while (1) {
                $events = inotify_read($fd);
                $newFile = realpath($this->folder.'/'.$events[0]['name']);
                $pathInfo = pathinfo($newFile);
                $extension = is_array($pathInfo) && isset($pathInfo["extension"]) ? $pathInfo["extension"] : '';
                if (in_array($extension, $allowedExtensions)) {
                    $output->writeln(
                        sprintf("New file:  <info>%s</info>\nStarting searching for subtitle...", $events[0]['name'])
                    );
                    foreach ($this->language as $language) {
                        $command = sprintf(
                            "./lazybot subtitle:addic7ed -i %s -l %s",
                            escapeshellarg($newFile),
                            $language
                        );
                        $process = new Process($command);
                        $output->writeln($command);
                        $process->setTimeout(60 * 60 * 24); //24Hours
                        $process->start(
                            function ($type, $buffer) use ($output) {
                                if ('err' === $type) {
                                    $output->writeln("\nERROR >$buffer");
                                } else {
                                    $output->writeln($buffer);
                                }
                            }
                        );
                    }
                }
            }
            inotify_rm_watch($fd, $watch_descriptor);
            fclose($fd);
        }
    }
}
