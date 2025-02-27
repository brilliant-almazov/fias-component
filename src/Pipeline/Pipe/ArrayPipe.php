<?php

declare(strict_types=1);

namespace Liquetsoft\Fias\Component\Pipeline\Pipe;

use Exception;
use InvalidArgumentException;
use Liquetsoft\Fias\Component\Exception\PipeException;
use Liquetsoft\Fias\Component\Pipeline\State\State;
use Liquetsoft\Fias\Component\Pipeline\Task\LoggableTask;
use Liquetsoft\Fias\Component\Pipeline\Task\Task;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Объект, который содержит внутренний массив со списком операций для исполнения.
 */
class ArrayPipe implements Pipe
{
    protected string $id;

    /**
     * @var Task[]
     */
    protected array $tasks;

    protected ?Task $cleanupTask;

    protected ?LoggerInterface $logger;

    /**
     * @param iterable             $tasks       Список задач, которые должны быть исполнены данной очередью
     * @param Task|null            $cleanupTask Задача, которая будет выполнена после исключения или по успешному завершению очереди
     * @param LoggerInterface|null $logger      PSR-3 совместимый объект для записи логов
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct(iterable $tasks, ?Task $cleanupTask = null, ?LoggerInterface $logger = null)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->tasks = $this->checkAndReturnTaskArray($tasks);
        $this->cleanupTask = $cleanupTask;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function run(State $state): Pipe
    {
        $this->proceedStart($state);

        foreach ($this->tasks as $task) {
            try {
                $this->proceedTask($state, $task);
            } catch (Throwable $e) {
                $this->proceedException($state, $task, $e);
            }
            if ($state->isCompleted()) {
                break;
            }
        }

        $this->proceedComplete($state);
        $this->proceedCleanup($state);

        return $this;
    }

    /**
     * Обработка запуска очереди.
     *
     * @param State $state
     */
    protected function proceedStart(State $state): void
    {
        $message = sprintf(
            "Start '%s' pipeline with '%s' state.",
            \get_class($this),
            \get_class($state)
        );

        $this->log(LogLevel::INFO, $message);
    }

    /**
     * Запускает задачу на исполнение.
     *
     * @param State $state
     * @param Task  $task
     *
     * @throws Exception
     */
    protected function proceedTask(State $state, Task $task): void
    {
        $taskName = $this->getTaskId($task);

        $this->log(
            LogLevel::INFO,
            "Start '{$taskName}' task.",
            [
                'task' => $taskName,
            ]
        );

        $this->injectLoggerToTask($task);
        $task->run($state);

        $this->log(
            LogLevel::INFO,
            "Complete '{$taskName}' task.",
            [
                'task' => $taskName,
            ]
        );
    }

    /**
     * Обрабатывает исключение во время работы очереди.
     *
     * @param Task      $task
     * @param State     $state
     * @param Throwable $e
     *
     * @throws PipeException
     */
    protected function proceedException(State $state, Task $task, Throwable $e): void
    {
        $taskName = $this->getTaskId($task);
        $message = "There was an error while running '{$taskName}' task. Pipeline was interrupted.";

        $this->log(
            LogLevel::INFO,
            $message,
            [
                'task' => $taskName,
            ]
        );

        $this->proceedCleanup($state);

        throw new PipeException($message, 0, $e);
    }

    /**
     * Обработка завершения задачи.
     *
     * @param State $state
     *
     * @throws Exception
     */
    protected function proceedCleanup(State $state): void
    {
        if ($this->cleanupTask) {
            $this->log(LogLevel::INFO, 'Start cleaning up.');
            $this->proceedTask($state, $this->cleanupTask);
        } else {
            $this->log(LogLevel::INFO, 'Skip cleaning up.');
        }
    }

    /**
     * Обработка завершения очереди.
     *
     * @param State $state
     */
    protected function proceedComplete(State $state): void
    {
        $state->complete();
        $this->log(LogLevel::INFO, "Pipeline '" . \get_class($this) . "' was completed.");
    }

    /**
     * Записывает в лог данные.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $context = $this->createLoggerContext($context);
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Добавляет объект для записи логов в операцию, если операция это поддерживает.
     *
     * @param Task $task
     */
    protected function injectLoggerToTask(Task $task): void
    {
        if ($task instanceof LoggableTask && $this->logger) {
            $task->injectLogger(
                $this->logger,
                $this->createLoggerContext(
                    [
                        'task' => $this->getTaskId($task),
                    ]
                )
            );
        }
    }

    /**
     * Возвращает контекст для записи логов по умолчанию.
     *
     * @param array $currentContext
     *
     * @return array
     */
    protected function createLoggerContext(array $currentContext = []): array
    {
        $defaultContext = [
            'pipeline_class' => \get_class($this),
            'pipeline_id' => $this->id,
        ];

        return array_merge($defaultContext, $currentContext);
    }

    /**
     * Проверяет все объекты массива на типы и возвращает его.
     *
     * @param iterable $tasks
     *
     * @return Task[]
     *
     * @throws InvalidArgumentException
     */
    protected function checkAndReturnTaskArray(iterable $tasks): array
    {
        $return = [];

        foreach ($tasks as $key => $task) {
            if (!($task instanceof Task)) {
                throw new InvalidArgumentException(
                    "Task with key '{$key}' must be an '" . Task::class . "' instance."
                );
            }
            $return[] = $task;
        }

        return $return;
    }

    /**
     * Возвращает идентификатор операции.
     *
     * @param Task $task
     *
     * @return string
     */
    protected function getTaskId(Task $task): string
    {
        return \get_class($task);
    }
}
