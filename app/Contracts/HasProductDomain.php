<?php

declare(strict_types=1);

namespace App\Contracts;

interface HasProductDomain
{
    public function getProductDomainName(): string;
}
