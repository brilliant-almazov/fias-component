<?php

declare(strict_types=1);

namespace Liquetsoft\Fias\Component\Tests\Pipeline\State;

use Liquetsoft\Fias\Component\Pipeline\State\ArrayState;
use Liquetsoft\Fias\Component\Tests\BaseCase;

/**
 * Тест для объекта, который хранит состояние во внутреннем массиве.
 */
class ArrayStateTest extends BaseCase
{
    /**
     * Проверяем запись и получение параметра.
     */
    public function testSetAndGetParameter()
    {
        $parameterName = $this->createFakeData()->word;
        $parameterValue = $this->createFakeData()->word;

        $state = new ArrayState;
        $state->setParameter($parameterName, $parameterValue);

        $this->assertSame($parameterValue, $state->getParameter($parameterName));
    }

    /**
     * Проверяем флаг, который мягко прерывает исполнение операций.
     */
    public function testComplete()
    {
        $state = new ArrayState;

        $stateCompleted = new ArrayState;
        $stateCompleted->complete();

        $this->assertFalse($state->isCompleted());
        $this->assertTrue($stateCompleted->isCompleted());
    }
}
