<?php

namespace App\Service;

use MongoDB\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MongoDB extends Client {
    public function __construct(
        ParameterBagInterface $parameters,
    )
    {
         parent::__construct($parameters->get('mongodb'));
    }
}
