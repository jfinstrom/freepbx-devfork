<?php

namespace Jfinstrom\FreepbxDevclone;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Github\Client;
use Gitonomy\Git\Admin;
use RuntimeException;

class DevForkCommand extends Command
{
    private $config;

    protected static $defaultName = 'devfork';

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('devfork')
            ->setDescription('Fork and update FreePBX modules')
            ->addArgument('modules', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The names of the modules to update')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'The branch to switch to (e.g., release/17.0)', 'master')
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Your GitHub username')
            ->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Your GitHub personal access token')
            ->addOption('clone-path', null, InputOption::VALUE_OPTIONAL, 'The path for cloning repositories')
            ->addOption('web-path', null, InputOption::VALUE_OPTIONAL, 'The path for the web modules');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $configFile = $_SERVER['HOME'] . '/.devfork.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
        }

        $username = $input->getOption('username') ?: $config['github']['username'];
        $token = $input->getOption('token') ?: $config['github']['token'];
        $clonePath = $input->getOption('clone-path') ?: $config['paths']['clone'];
        $webPath = $input->getOption('web-path') ?: $config['paths']['web'];

        $username = $username ?: $helper->ask($input, $output, new Question('Enter your GitHub username: ', $config['github']['username']));
        $token = $token ?: $helper->ask($input, $output, new Question('Enter your GitHub personal access token: ', $config['github']['token']));
        $clonePath = $clonePath ?: $helper->ask($input, $output, new Question('Enter the path for cloning repositories: ', $config['paths']['clone']));
        $webPath = $webPath ?: $helper->ask($input, $output, new Question('Enter the path for the web modules: ', $config['paths']['web']));

        $this->config['github']['username'] = $username;
        $this->config['github']['token'] = $token;
        $this->config['paths']['clone'] = $clonePath;
        $this->config['paths']['web'] = $webPath;

        $modules = $input->getArgument('modules');
        $branch = $input->getOption('branch');

        foreach ($modules as $module) {
            $sourcePath = $this->forkAndCloneRepo($module, $branch, $username, $token);
            $this->updateWebModule($module, $sourcePath, $webPath, true);

            $output->writeln("Module '{$module}' forked, cloned, and updated successfully.");
        }

        $output->writeln('DevFork command executed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Forks and clones a repository from GitHub.
     *
     * @param string $repoName The name of the repository to fork and clone.
     * @param string $branch The branch to clone.
     * @param string $username The username of the organization to fork the repository to.
     * @param string $token The token for authentication.
     * @throws RuntimeException If the repository fails to fork.
     * @return string The path where the repository is cloned.
     */
    private function forkAndCloneRepo($repoName, $branch, $username, $token)
    {
        // Create a GitHub API client
        $client = new Client();
        $client->authenticate($token, null, \Github\AuthMethod::ACCESS_TOKEN);

        // Fork the repository
        try {
            $forkData = $client->repo()->forks()->create('FreePBX', $repoName);
            $forkedRepo = $forkData['full_name'];
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to fork repository: ' . $e->getMessage());
        }
        $cloneUrl = "git@github.com:{$forkedRepo}.git";
        $clonePath = $this->config['paths']['clone'] . $repoName;
        if (!file_exists($clonePath)) {
            mkdir($clonePath, 0755, true);
            chown($clonePath, 'asterisk');
            Admin::cloneBranchTo($clonePath, $cloneUrl, $branch, false);
        }

        return $clonePath;
    }

    /**
     * Updates the web module with the provided module name, source path, web path, and optional installation flag.
     *
     * @param string $moduleName The name of the module.
     * @param string $sourcePath The path of the module source.
     * @param string $webPath The path of the web module.
     * @param bool $install (Optional) Flag indicating whether to install the module.
     * @return void
     */
    private function updateWebModule($moduleName, $sourcePath, $webPath, $install = false)
    {
        exec("rm -rf {$webPath}/{$moduleName}");
        exec("ln -s {$sourcePath} {$webPath}/{$moduleName}");

        if ($install) {
            exec("fwconsole ma install {$moduleName}");
        }
    }
}
