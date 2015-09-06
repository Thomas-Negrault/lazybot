<?php
/**
 * Author: Thomas Negrault
 * Date: 05/04/15
 */
namespace lazybot;

use Goutte\Client;
use GuzzleHttp\Stream\Stream;
use PHPushbullet\PHPushbullet;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
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
    protected $frequency = 15;

    /** @var Client $client */
    protected $client;

    /** @var bool */
    protected $finish = false;

    /** @var bool $highestPercent */
    protected $highestPercent = 0;

    /** @var  array $config */
    protected $config;

    /** @var  array $userConfig */
    protected $userConfig;

    /**
     * Command configuration
     */
    protected function configure()
    {
        date_default_timezone_set("Europe/Paris");
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
        $configLoader = new FileLocator(__DIR__.'/../../app/config');
        $this->config = Yaml::parse($configLoader->locate('parameters.yml'))["parameters"];
        $this->userConfig = Yaml::parse($configLoader->locate('userConfig.yml'));

        try {
            $this->checkInput();
            $output->writeln(
                sprintf(
                    "Checking subtitles for file <info>%s</info> for <info>%s</info> language\nOutput directory: <info>%s</info>",
                    $this->inputFile,
                    $this->language,
                    $this->path
                )
            );
            do {
                $output->writeln(
                    sprintf("\n<comment>%s</comment>\t Frequency: %d minute(s)", date("d-m-Y H:i"), $this->frequency)
                );
                if ($this->getData()) {
                    $this->handleResults($output);
                }
                if ($this->finish == true) {
                    break;
                } else {
                    $output->writeln(
                        sprintf(
                            "Progress: <error>%d</error>%%\nWaiting %d minute(s)...",
                            $this->highestPercent,
                            $this->frequency
                        )
                    );
                    $this->wait($this->frequency, $output);
                }
            } while ($this->finish == false);
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        return null;
    }

    /**
     * @param $filename
     */
    protected function notify($filename)
    {
        $pushBulletConfig = $this->userConfig["pushbullet"];

        if ($pushBulletConfig["enable"] == true) {
            $pushbullet = new PHPushbullet($pushBulletConfig["key"]);
            $pushbullet->all()->note("Subtitle: ".$this->inputFile, "Path: ".$filename);
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function handleResults(OutputInterface $output)
    {
        $subtitles   = $this->results[$this->language];
        $countryCode = isset($this->config["countries"][$this->language])?$this->config["countries"][$this->language]: '';
        $index = 0;
        foreach ($subtitles as $subtitle) {
            $progress = $subtitle["progress"];
            if ($progress > $this->highestPercent) {
                $this->highestPercent = $progress;
            }

            if ($progress == 100) {
                $file           = $this->download($subtitle['links'][0]);
                $outputFilename = $this->generateOutputFilename($index, $countryCode);
                $index++;

                $this->writeFile($file, $outputFilename, $output);
            } elseif ($progress > 85) {
                $this->frequency = 5;
            }
        }

        $this->results = null;
    }

    /**
     * @return bool
     */
    protected function getData()
    {
        $link        = $this->config["links"]["addic7ed"]["search"];
        $requestLink = sprintf($link, $this->inputFile);

        $this->client = new Client();
        $this->client->followRedirects(true);

        $this->connect();
        /** @var crawler $crawler */
        $crawler = $this->client->request('GET', $requestLink);
        if ($this->client->getHistory()->current()->getUri() == $requestLink) {
            return false;
        } else {
            $crawler->filter('div#container95m td.language')->each(
                function ($language) {
                    $this->parseLanguage($language);
                }
            );

            return true;
        }
    }

    /**
     * @return bool
     */
    protected function connect()
    {
        $url     = $this->config["links"]["addic7ed"]["login"];
        $credentials = isset($this->userConfig["credentials"]) ? $this->userConfig["credentials"] : array();
        $crawler = $this->client->request(
            'POST',
            $url,
            $credentials
        );

        if ($this->checkConnection($crawler) !== true) {
            throw new Exception('Connection failed (check username/password in app/config/userConfig.yml');
        };
    }

    /**
     * Check if client is connected
     *
     * @param  Crawler $crawler
     * @return bool
     */
    protected function checkConnection(Crawler $crawler)
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
     * @param Link $link
     * @return string
     */
    protected function download(Link $link)
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
     * @param                 $subtitle
     * @param                 $fileName
     * @param OutputInterface $output
     */
    protected function writeFile($subtitle, $fileName, OutputInterface $output)
    {
        $output->writeln('Generating file');
        $outputFile = $this->path.'/'.$fileName;
        if (file_put_contents($outputFile, $subtitle)) {
            $output->writeln(
                sprintf('<info>File %s save with success</info>', $outputFile)
            );
            $this->notify($outputFile);
            $this->finish = true;
        } else {
            throw new Exception('Error during file saving');
        }

    }

    /**
     *
     */
    protected function checkInput()
    {
        $realpath        = realpath($this->inputFile);
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

    /**
     * @param $minutes
     * @param $output
     */
    protected function wait($minutes, $output)
    {
        $progress = new ProgressBar($output, $minutes);

        for ($i = 0; $i < $minutes; $i++) {
            sleep(60);
            $progress->advance();
        }

    }

    /**
     * @param int    $index
     * @param string $countryCode
     * @return mixed
     */
    protected function generateOutputFilename($index = 0, $countryCode = '')
    {
        $regex  = '/^(.*)\.([a-zA-Z0-9]{0,4})$/';
        $suffix = '';
        if ($index != 0) {
            $suffix[] = $index;
        }
        if ($countryCode != '') {
            $suffix[] = $countryCode;
        }
        $suffix[]    = 'srt';
        $replacement = '${1}'.'.'.implode('.', $suffix);

        return preg_replace($regex, $replacement, $this->inputFile);
    }
}