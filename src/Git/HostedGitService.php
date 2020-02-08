<?php

namespace Appellation\Git;

use Millesime\Package;

interface HostedGitService
{
    /**
     * @param string $project
     * @param string $tag
     *
     * @return Version
     */
    public function getVersion(string $project, string $tag) : Version;

    /**
     * @param Release $release
     * @param Package $package
     *
     * @return void
     */
    public function uploadAsset(Release $release, Package $package);
}