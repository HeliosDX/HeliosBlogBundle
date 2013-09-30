<?php

namespace Helios\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeliosUserBundle extends Bundle
{
  public function getParent()
  {
    return 'FOSUserBundle';
  }
}
