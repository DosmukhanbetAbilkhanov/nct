<?php

namespace App\Exceptions;

class ProductNotFoundException extends NationalCatalogException
{
    public function __construct(public string $gtin)
    {
        parent::__construct("Product not found in National Catalog for GTIN: {$gtin}");
    }
}
