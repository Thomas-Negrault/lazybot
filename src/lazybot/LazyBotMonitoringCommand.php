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
     * @var string $language
     */
    protected $language;


    protected function configure()
    {
        $this->setName('monitor');
        $this->setDescription('Monitor a folder for changes and search subtitles on Addic7ed');

        $this->addArgument('folder', InputArgument::REQUIRED);
        $this->addOption('language', 'l', InputOption::VALUE_REQUIRED, '', 'french');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->folder   = realpath($input->getArgument('folder'));
        $this->language = ucfirst($input->getOption("language"));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $download_dir = $this->folder;
        $output->writeln(sprintf("Starting monitoring folder <info>%s</info>", $this->folder));

        $fd               = inotify_init();
        $watch_descriptor = inotify_add_watch($fd, $download_dir, IN_CREATE);
        $fileInfo         = finfo_open(FILEINFO_MIME_TYPE);
        while (1) {
            $events  = inotify_read($fd);
            $newFile = realpath($this->folder.'/'.$events[0]['name']);
            $mime    = finfo_file($fileInfo, $newFile);
            if (preg_match('/video\/.*/', $mime)) {
                $output->writeln(
                    sprintf("New file:  <info>%s</info>\nStarting searching for subtitle...", $events[0]['name'])
                );

                $process = new Process('./lazybot subtitle:addic7ed -i '.'"'.$newFile.'"');
                $process->run(
                    function ($type, $buffer) {
                        if ('err' === $type) {
                            echo 'ERR > '.$buffer;
                        } else {
                            echo 'OUT > '.$buffer;
                        }
                    }
                );

            }
        }
        inotify_rm_watch($fd, $watch_descriptor);
        fclose($fd);
    }

}