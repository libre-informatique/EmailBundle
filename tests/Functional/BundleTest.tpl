<?php

namespace {NameSpace}\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

class {ClassName}Test extends KernelTestCase
{
  
protected $container;
  
protected function setUp()
{
    static::bootKernel();
        
    /** @var Container $container */
    $this->container = self::$kernel->getContainer();
}

public function testServicesAreInitializable()
{
        
    $serviceIds = array_filter($this->container->getServiceIds(), function ($serviceId) {
        return 0 === strpos($serviceId, {BundleService} . '.');
    });
        
    foreach ($serviceIds as $serviceId) {
        $this->assertNotNull($this->container->get($serviceId));
    }
}
}
