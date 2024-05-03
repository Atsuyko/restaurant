<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
  public function testTheAutomaticApiTokenSettingWhenAnUserIsCreated(): void
  {
    $user = new User();
    $this->assertNotNull($user->getApiToken());
  }

  public function provideFirstName(): \Generator
  {
    yield ['Thomas'];
    yield ['Eric'];
    yield ['Marie'];
  }

  /** @dataProvider provideFirstName */
  public function testFirstNameSetter(string $name): void
  {
    $user = new User();
    $user->setFirstName($name);
    $this->assertSame($name, $user->getFirstName());
  }
}
