<?php

namespace Appellation;

class Version
{
	private string $cloneurl;
	private string $uploadurl;
	private string $tag;

	public function __construct(string $cloneurl, string $uploadurl, string $tag)
	{
		$this->cloneurl = $cloneurl;
		$this->uploadurl = $uploadurl;
		$this->tag = $tag;
	}

	public function getCloneUrl() : string
	{
		return $this->cloneurl;
	}

	public function getUploadUrl() : string
	{
		return $this->uploadurl;
	}

	public function getTag() : string
	{
		return $this->tag;
	}
}