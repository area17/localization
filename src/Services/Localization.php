<?php

namespace A17\Localization\Services;

class Localization
{
    protected array $config = [];

    public function instance(): self
    {
        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }
}
