<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Utils;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Coroutine as Co;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Engine\Exception\RunningInNonCoroutineException;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Throwable;

class Coroutine
{
    /**
     * Returns the current coroutine ID.
     * Returns -1 when running in non-coroutine context.
     */
    public static function id(): int
    {
        return Co::id();
    }

    public static function defer(callable $callable): void
    {
        Co::defer(static function () use ($callable) {
            try {
                $callable();
            } catch (Throwable $throwable) {
                static::printLog($throwable);
            }
        });
    }

    public static function sleep(float $seconds): void
    {
        usleep(intval($seconds * 1000 * 1000));
    }

    /**
     * Returns the parent coroutine ID.
     * Returns 0 when running in the top level coroutine.
     * @throws RunningInNonCoroutineException when running in non-coroutine context
     * @throws CoroutineDestroyedException when the coroutine has been destroyed
     */
    public static function parentId(?int $coroutineId = null): int
    {
        return Co::pid($coroutineId);
    }

    /**
     * @return int Returns the coroutine ID of the coroutine just created.
     *             Returns -1 when coroutine create failed.
     */
    public static function create(callable $callable): int
    {
        $coroutine = Co::create(static function () use ($callable) {
            try {
                $callable();
            } catch (Throwable $throwable) {
                static::printLog($throwable);
            }
        });

        try {
            return $coroutine->getId();
        } catch (\Throwable) {
            return -1;
        }
    }

    public static function inCoroutine(): bool
    {
        return Co::id() > 0;
    }

    private static function printLog(Throwable $throwable): void
    {
        if (ApplicationContext::hasContainer()) {
            $container = ApplicationContext::getContainer();
            if ($container->has(StdoutLoggerInterface::class)) {
                $logger = $container->get(StdoutLoggerInterface::class);
                if ($container->has(FormatterInterface::class)) {
                    $formatter = $container->get(FormatterInterface::class);
                    $logger->warning($formatter->format($throwable));
                } else {
                    $logger->warning((string) $throwable);
                }
            }
        }
    }
}
