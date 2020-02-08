<?php

namespace Appellation\Git;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client as HttpClient;
use Millesime\Package;

class GitlabClient implements HostedGitService
{
    const BASE_URI = 'https://gitlab.com';

    private string $token;
    private string $login;
    private HttpClient $httpClient;
    private LoggerInterface $logger;

    public function __construct($token, $login, $httpClient, $logger)
    {
        $this->token = $token;
        $this->login = $login;
        $this->httpClient = $httpClient ?: new HttpClient(['base_uri' => self::BASE_URI]);
        $this->logger = $logger ?: new NullLogger;
    }

    public function getVersion(string $project, string $tag) : Version
    {
        /**
         * Get a Release by a tag name
         * @see https://docs.gitlab.com/ee/api/releases/#get-a-release-by-a-tag-name
         */
        $request = new Psr7\Request(
            'GET',
            sprintf('/api/v4/projects/%s', urlencode($project)),
            [
                'Authorization' => 'Bearer '.$this->token,
            ]
        );
        $response = $this->httpClient->send($request, ['debug' => true]);
        $gitlabProject = json_decode($response->getBody());
        $clone_url = str_replace(
            'https://',
            'https://'.$this->login.':'.$this->token.'@',
            $gitlabProject->web_url
        );

        /**
         * Get a Release by a tag name
         * @see https://docs.gitlab.com/ee/api/releases/#get-a-release-by-a-tag-name
         */
        $request = new Psr7\Request(
            'GET',
            sprintf('/api/v4/projects/%s/releases/%s', urlencode($project), $tag),
            [
                'Authorization' => 'Bearer '.$this->token,
            ]
        );
        $response = $this->httpClient->send($request, ['debug' => true]);
        $release = json_decode($response->getBody());
        $upload_url = str_replace(':id', urlencode($project), '/api/v4/projects/:id/uploads');

        return new Version($clone_url, $upload_url, $tag);
    }

    public function uploadAsset(Release $release, Package $package)
    {
        /**
         * Upload a file
         * @see https://docs.gitlab.com/ee/api/projects.html#upload-a-file
         */
        $respone = $this->httpClient->request(
            'POST', 
            $release->getVersion()->getUploadUrl(), 
            [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen(
                            $release->getProject()->getWorkingDirectory().DIRECTORY_SEPARATOR.$package->getName(),
                            'r'
                        ), 
                        'headers' => [
                            'Authorization' => 'Bearer '.$this->token,
                        ]
                    ]
                ],
                'debug' => true,
            ]
        );

        var_dump($response);

        /**
         * Create a link
         * @see https://docs.gitlab.com/ee/api/releases/links.html#create-a-link
         */
    }
}
