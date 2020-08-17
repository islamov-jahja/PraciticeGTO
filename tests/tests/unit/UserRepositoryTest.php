<?php

use App\Persistance\Repositories\User\UserRepository;
use DI\ContainerBuilder;
use Tests\tests\_data\Container;

class UserRepositoryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    /**@var $userRepository UserRepository*/
    private $userRepository;
    protected function _before()
    {
        $container = Container::get();
        $this->userRepository = $container->get(UserRepository::class);
    }

    protected function _after()
    {
    }

    // tests
    public function testAddingUser()
    {
        /**@var $user \App\Domain\Models\User\User*/
        $user = new \App\Domain\Models\User\User(-1, 'Тестовый пользователь','123', 'test@mail.ru', 3,0,new DateTime(), 1, new DateTime());
        $userId = $this->userRepository->add($user);
        $this->idCreatingUSer = $userId;
        $this->tester->assertNotEquals(null, $userId);
    }

    public function testGettingUserWithEmail()
    {
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $this->tester->assertEquals('Тестовый пользователь', $user->getName());
    }

    public function testGettingWithId()
    {
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $user = $this->userRepository->get($user->getId());
        $this->tester->assertEquals('Тестовый пользователь', $user->getName());
    }

    public function testUpdateUser()
    {
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $user->setIsActivity();
        $this->userRepository->update($user);
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $this->tester->assertEquals(true, $user->isActivity());
    }

    public function testDeleteUser()
    {
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $this->userRepository->delete($user->getId());
        $user = $this->userRepository->getByEmail('test@mail.ru');
        $this->tester->assertEquals(null, $user);
    }
}