<?php

namespace Controla\Tests\Support\Views;

use Cubo\View\View;

/**
 * Faz o papel da DefaultView nos testes, sem o singleton nem o layout real.
 *
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class LayoutFake extends View
{
    public function __construct()
    {
        $this->setTemplate('layout_fake.php');
    }
}
