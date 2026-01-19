<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Document\Customer;

class CustomerTest extends TestCase
{
    public function testGetRolesAlwaysContainsRoleUser()
    {
        $c = new Customer();
        $c->setRoles([]);
        $roles = $c->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testPasswordNullableSetter()
    {
        $c = new Customer();
        $c->setPassword(null);
        $this->assertNull($c->getPassword());

        $c->setPassword('hashed');
        $this->assertEquals('hashed', $c->getPassword());
    }
}
