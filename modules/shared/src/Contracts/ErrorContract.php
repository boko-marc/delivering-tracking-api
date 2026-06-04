<?php

declare(strict_types=1);

namespace Module\Shared\Contracts;

interface ErrorContract
{
    public function code(): string;

    public function translationKey(): string;
}
