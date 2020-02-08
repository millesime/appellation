<?php

namespace Appellation\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Appellation\Appellation;

class Publish extends Command
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publish .phar packages of your release.')
            ->setHelp('Add your archives built with Millesime to your releases assets into your DevOps lifecycle tool. Compatible with Github and Gitlab.')

            ->addArgument('repo', InputArgument::REQUIRED, '<vendor/repository>')
            ->addArgument('tag', InputArgument::OPTIONAL, 'branch, tag or commit', 'master')

            ->addOption('user', 'u', InputOption::VALUE_NONE, 'User identifier')
            ->addOption('token', 't', InputOption::VALUE_NONE, 'Authorization token')
            ->addOption('service', 's', InputOption::VALUE_REQUIRED, 'Git service', 'github')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not push after build. Just checkout and build.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        switch ($input->getOption('service')) {
            case 'gitlab':
                $envUser = getenv('GITLAB_API_USER');
                $envToken = getenv('GITLAB_API_TOKEN');
                break;
            case 'github':
            default:
                $envUser = getenv('GITHUB_API_USER');
                $envToken = getenv('GITHUB_API_TOKEN');
                break;
        }

        if ($input->getOption('token')) {
            $question = new Question('Enter token:');
            $question->setHidden(true);
            $token = $helper->ask($input, $output, $question);
            $input->setOption('token', $token);
        } elseif(false!==$envToken) {
            $input->setOption('token', $envToken);
        }

        if ($input->getOption('user')) {
            $question = new Question('Enter your user name:');
            $user = $helper->ask($input, $output, $question);
            $input->setOption('user', $user);
        } elseif(false!==$envUser) {
            $input->setOption('user', $envUser);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $login = $input->getOption('user');
        $token = $input->getOption('token');
        $service = $input->getOption('service');

        $repo = $input->getArgument('repo');
        $tag = $input->getArgument('tag');

        $logger = new ConsoleLogger($output);
        $appellation = new Appellation($service, $login, $token, null, $logger);

        /* build packages from remote git repository */
        $release = $appellation->from($repo, $tag);

        /* push the packages into git service */
        if (false===$input->getOption('dry-run')) {
            $appellation->publish($release);
        }

        return 0;
    }
}
