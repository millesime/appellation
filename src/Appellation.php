<?php

namespace Appellation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use GuzzleHttp\Client as HttpClient;
use Millesime\Millesime;

class Appellation
{
    private Millesime $millesime;
    private LoggerInterface $logger;

    public function __construct(
        $gitservice,
        $login,
        $token,
        HttpClient $httpClient = null,
        LoggerInterface $logger = null
    ) {
        $this->millesime = new Millesime($logger);
        $this->logger = $logger ?: new NullLogger;

        $services = [
            'github' => Git\GithubClient::class,
            'gitlab' => Git\GitLabClient::class,
        ];

        $this->gitService = new $services[$gitservice]($token, $login, $httpClient, $this->logger);
    }

    /**
     * Get release info from gitservice, checkout the source code, build phar packages with millesime
     *
     * @param string $repo   The project to clone (<vendor/repo>).
     * @param string $branch The branch to checkout.
     */
    public function from(string $repo, string $branch) : Release
    {
        /* ask gitservice where is the version of the project */
        $version = $this->gitService->getVersion($repo, $branch);

        /* checkout the project into PROJECTS_DIRECTORY subdir */
        $project = new Project($version, PROJECTS_DIRECTORY, $this->logger);

        /* build phar archives with Millesime */
        $packages = $project->buildPackages($this->millesime);

        return new Release($project, $version, $packages);
    }

    /**
     * Push built packages to gitservice release metadata. 
     */
    public function publish(Release $release)
    {
        $packages = $release->getPackages();

        foreach ($packages as $package) {
            $this->gitService->uploadAsset($release, $package);
        }
    }
}
