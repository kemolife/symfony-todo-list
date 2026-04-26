<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CsvImportRequest
{
    #[Assert\NotBlank]
    #[Assert\File(mimeTypes: ['text/csv'], mimeTypesMessage: 'Please upload a valid CSV file')]
    public $csvFilePath;
}
