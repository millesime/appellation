<?php

namespace Appellation;

class Release
{
    private Project $project;
    private Version $version;
    private Iterable $packages;

    /**
     * @param Project             $project
     * @param Version             $version
     * @param Millesime\Package[] $packages
     */
    public function __construct(
        Project $project,
        Version $version,
        Iterable $packages
    ) {
        $this->project = $project;
        $this->version = $version;
        $this->packages = $packages;
    }

    public function getProject() : Project
    {
        return $this->project;
    }

    public function getVersion() : Version
    {
        return $this->version;
    }

    /**
     * @return Millesime\Package[]
     */
    public function getPackages() : Iterable
    {
        return $this->packages;
    }
}
