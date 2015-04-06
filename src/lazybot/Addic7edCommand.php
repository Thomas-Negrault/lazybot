<?php
/**
 * Author: Thomas Negrault
 * Date: 05/04/15
 */
namespace lazybot;

use Goutte\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class Addic7edCommand extends Command
{
    /**
     * @var string $inputFile
     */
    protected $inputFile;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var string $language
     */
    protected $language;

    /**
     * @var
     */
    protected $results;

    /**
     * Command configuration
     */
    protected function configure()
    {
        $this->setName('subtitle:addic7ed');
        $this->setDescription('check subtitles on Addic7ed');

        $this->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Input file');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('language', 'l', InputOption::VALUE_REQUIRED, '', 'french"');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->inputFile = $input->getOption("input");
        $this->path      = $input->getOption("path");
        $this->language  = $input->getOption("language");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello");
        $this->getDatas();
        dump($this->results);

        return null;

    }

    /**
     * Generate result array
     */
    protected function getDatas()
    {

        $link        = "http://www.addic7ed.com/search.php?search=%s&Submit=Search";
        $requestLink = sprintf($link, $this->inputFile);

        $client = new Client();
        /** @var crawler $crawler */
        $crawler = $client->request('GET', $requestLink);

        $crawler->filter('div#container95m td.language')->each(
            function ($language) {
                $this->parseLanguage($language);
            }
        );
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
                return $link->link()->getUri();
            }
        );
    }


}