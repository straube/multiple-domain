<?php

class MultipleDomainTest extends WP_UnitTestCase
{

    /**
     * @test
     */
    public function itHasTheCurrentDomain()
    {
        $plugin = MultipleDomain::instance();
        $domain = $plugin->getDomain();
        $this->assertEquals($domain, 'example.org');
    }

    /**
     * @test
     */
    public function itHasAllDomains()
    {
        $plugin = MultipleDomain::instance();
        $domains = $plugin->getDomains();
        $this->assertInternalType('array', $domains);
    }
}
