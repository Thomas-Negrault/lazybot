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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

class Addic7edCommand extends Command
{
    /** @var string $inputFile */

    protected $inputFile;

    /** @var string $path */

    protected $path;

    /** @var string $language */
    protected $language;

    /** @var array $results */
    protected $results;

    /** @var int $frequency */
    protected $frequency = 30;

    /** @var Client $client */
    protected $client;

    /**
     * Command configuration
     */
    protected function configure()
    {
        $this->setName('subtitle:addic7ed');
        $this->setDescription('check subtitles on Addic7ed');

        $this->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Input file');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '');
        $this->addOption('language', 'l', InputOption::VALUE_REQUIRED, '', 'french');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->inputFile = $input->getOption("input");
        $this->path      = $input->getOption("path");
        $this->language  = ucfirst($input->getOption("language"));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $configLoader = new FileLocator(__DIR__.'/../../app/config');
//        $config = Yaml::parse($configLoader->locate('parameters.yml'));

        try {
            $this->checkInput();
            $output->writeln(
                sprintf(
                    "Checking subtitles for file <comment>%s</comment>\nOutput directory: <comment>%s</comment>",
                    $this->inputFile,
                    $this->path
                )
            );

            $this->getDatas();
            $this->handleResults($output);

        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        return null;

    }

    protected function handleResults(OutputInterface $output)
    {
        $subtitles = $this->results[$this->language];

        foreach($subtitles as $subtitle){
            dump($subtitle["progress"]);
        }
die;

//        $subtitle = $this->download($link);
//        $this->writeFile($subtitle, $output);
    }

    /**
     * Generate result array
     */
    protected function getDatas()
    {

        $link        = "http://www.addic7ed.com/search.php?search=%s&Submit=Search";
        $requestLink = sprintf($link, $this->inputFile);

        $this->client = new Client();
        $this->client->followRedirects(true);

        $this->connect();
        /** @var crawler $crawler */
        $crawler = $this->client->request('GET', $requestLink);

        $crawler->filter('div#container95m td.language')->each(
            function ($language) {
                $this->parseLanguage($language);
            }
        );

    }

    /**
     * @return bool
     */
    protected function connect()
    {

        $url     = "http://www.addic7ed.com/dologin.php";
        $crawler = $this->client->request(
            'POST',
            $url,
            array('username' => 'lazybot', 'password' => 'azerty', 'remember' => true)
        );

        if ($this->checkConnection($crawler) !== true) {
            throw new Exception('Connection failed (check username/password');
        };

    }

    /**
     * Check if client is connected
     *
     * @param $crawler
     * @return bool
     */
    protected function checkConnection($crawler)
    {
        $text = $crawler->filter('#hBar a.button')->first()->text();
        if ($text == "Signup") {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get release data
     *
     * @param Crawler $language
     */
    protected function parseLanguage(Crawler $language)
    {
        if ($language->count()) {
            $country  = trim($language->text());
            $progress = $this->getProgress(trim($language->nextAll()->text()));
            $links    = $this->getLinks($language);

            $this->results[$country][] = array("progress" => $progress, "links" => $links);
        }
    }

    /**
     * Get process from string to number
     *
     * @param string $stringProgress
     * @return float
     */
    protected function getProgress($stringProgress)
    {
        $regex = "/^([0-9.]*)% Completed$/";
        if (preg_match($regex, $stringProgress, $matches)) {
            $progress = (float)$matches[1];
        } elseif ($stringProgress == "Completed") {
            $progress = 100;

        } else {
            $progress = 0;
        }

        return $progress;

    }

    /**
     * Get links for a language
     *
     * @param Crawler $language
     * @return array
     */
    protected function getLinks(Crawler $language)
    {
        return $language->nextAll()->nextAll()->filter('a.buttonDownload')->each(
            function ($link) {
                return $link->link();
            }
        );
    }

    /**
     * @param $link
     * @return string
     */
    protected function download($link)
    {
        /** @var Response $response */
        /** @var Stream $stream */

        $this->client->click($link);
        $response = $this->client->getResponse();
        $stream   = $response->getContent();
        $subtitle = $stream->getContents();

        return $subtitle;


    }

    /**
     * @param $subtitle
     */
    protected function writeFile($subtitle, OutputInterface $output)
    {
        $output->writeln('Generating file');
        if (file_put_contents($this->path.'/'.$this->inputFile.'.srt', $subtitle)) {
            $output->writeln(
                sprintf('<info>File %s save with success</info>', $this->path.'/'.$this->inputFile.'.srt')
            );
        } else {
            throw new Exception('Error during file saving');
        }

    }

    protected function checkInput()
    {
        $realpath = realpath($this->inputFile);
        $this->inputFile = basename($realpath);

        if ($realpath === false) {
            throw new Exception("File doesn't exist");
        }

        if ($this->path === null) {
            $this->path = dirname($realpath);
        } elseif (!is_dir($this->path)) {
            throw new Exception("Output directory doesn't exist or it's not a directory");
        }

    }

    protected function verifyFile()
    {
        if (realpath($this->inputFile) === false) {
            throw new Exception('Fichier non existant');
        }
    }

    protected function wait(int $minutes)
    {
        $sec = $minutes * 60;
        sleep($sec);
    }

}