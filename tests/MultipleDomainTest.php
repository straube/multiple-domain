<?php

use PHPUnit\Framework\TestCase;

class MultipleDomainTest extends TestCase
{

    /**
     * @test
     */
    public function itIsConstructed()
    {
        $plugin = new MultipleDomain();
        $this->assertInstanceOf(MultipleDomain::class, $plugin);
    }
}
