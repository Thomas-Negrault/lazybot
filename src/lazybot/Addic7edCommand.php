<?php
/**
 * Author: Thomas Negrault
 * Date: 05/04/15
 */
namespace lazybot;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function configure()
    {
        $this->setName('subtitle:addic7ed');
        $this->setDescription('check subtitles on Addic7ed');

        $this->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Input file');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->inputFile = $input->getOption("input");
        $this->path      = $input->getOption("path");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello");
        dump($this->inputFile);
        dump($this->inputFile);
    }

}