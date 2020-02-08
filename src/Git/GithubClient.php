<?php

namespace Appellation\Git;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client as HttpClient;
use Millesime\Package;

class GithubClient implements HostedGitService
{
    const BASE_URI = 'https://api.github.com';

    private string $token;
    private string $login;
    private HttpClient $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        string $token,
        string $login,
        HttpClient $httpClient = null,
        LoggerInterface $logger = null
    ) {
        $this->token = $token;
        $this->login = $login;
        $this->httpClient = $httpClient ?: new HttpClient(['base_uri' => self::BASE_URI]);
        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(string $project, string $tag) : Version
    {
        $request = new Psr7\Request(
            'GET',
            sprintf('/repos/%s/releases/tags/%s', $project, $tag),
            [
                'Authorization' => sprintf('token %s', $this->token),
                'Accept' => 'application/json',
                'User-Agent' => 'millesime/appelation',
            ]
        );

        $response = $this->httpClient->send($request);
        $release = json_decode($response->getBody());

        $clone_url = str_replace(
            'https://',
            'https://'.$this->login.':'.$this->token.'@',
            'https://github.com/'.$project
        );

        return new Version($clone_url, $release->upload_url, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function uploadAsset(Release $release, Package $package)
    {
        $request = new Psr7\Request(
            'POST',
            str_replace(
                '{?name,label}',
                '?name='.$package->getName().'&label='.$package->getName(),
                $release->getVersion()->getUploadUrl()
            ),
            [
                'Authorization' => sprintf('token %s', $this->token),
                'Accept' => 'application/json',
                'User-Agent' => 'millesime/appelation-website',
                'Content-Type' => mime_content_type($file),
                'Content-Length' => filesize($file),
            ],
            file_get_contents(
                $release->getProject()->getWorkingDirectory().DIRECTORY_SEPARATOR.$package->getName()
            ) // could cause a memory limit in case of large files. should be replaced by a stream.
        );

        $response = $this->httpClient->send($request);
    }
}
