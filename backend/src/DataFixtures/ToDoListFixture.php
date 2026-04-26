<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\TodoList;
use App\Enum\TodoStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class TodoListFixture extends Fixture
{
    protected Generator $faker;

    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create();
        $statuses = TodoStatus::cases();

        for ($i = 0; $i < 20; ++$i) {
            $list = (new TodoList())
                ->setName($this->faker->sentence())
                ->setDescription($this->faker->paragraph())
                ->setTag($this->faker->randomElement(['work', 'personal', 'shopping', 'health']))
                ->setStatus($this->faker->randomElement($statuses));

            $manager->persist($list);
        }

        $manager->flush();
    }
}
