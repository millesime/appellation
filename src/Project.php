<?php

namespace Appellation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Millesime\Millesime;
use Millesime\Package;
use Millesime\Manifest\Manifest;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

define('PROJECTS_DIRECTORY', sys_get_temp_dir());

class Project
{
    private LoggerInterface $logger;
    private string $id;
    private string $workingDirectory;
    private Manifest $manifest;

    public function __construct(
        Version $version,
        string $into = PROJECTS_DIRECTORY,
        LoggerInterface $logger = null
    ) {
        $this->id = uniqid();
        $this->logger = $logger ?: new NullLogger;
        $this->workingDirectory = implode(DIRECTORY_SEPARATOR, [$into, $this->id]);

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->workingDirectory);

        /**
         * Clone the remote-repository to the given version.
         * $ git clone --single-branch --branch <branchname> <remote-repo>
         */
        $process = new Process(
            [
                'git',
                'clone',
                '--single-branch',
                '--branch',
                $version->getTag(), // <branch>
                $version->getCloneUrl(), // <remote-repo>
                '.' // <dir>
            ], 
            $this->workingDirectory
        );
        $process->run(function ($type, $buffer) use ($logger) {
            $logger->debug($buffer);
        });
    }

    /**
     * @return Package[]
     */
    public function buildPackages(Millesime $millesime) : Iterable
    {
        $packages = $millesime($this->workingDirectory);

        $this->manifest = $millesime->getLastRelease()->getManifest();

        return $packages;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getWorkingDirectory() : string
    {
        return $this->workingDirectory;
    }

    /**
     * @return Manifest|null
     */
    public function getManifest()
    {
        return $this->manifest;
    }
}
