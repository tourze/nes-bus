<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Exception;

/**
 * 时序异常
 * 
 * 表示硬件时序相关的异常，如时钟周期不匹配等
 */
class TimingException extends BusException
{
    /**
     * 创建一个表示时钟周期不匹配的异常
     *
     * @param int $expected 期望的时钟周期
     * @param int $actual 实际的时钟周期
     * @return self
     */
    public static function cyclesMismatch(int $expected, int $actual): self
    {
        return new self(sprintf(
            '时钟周期不匹配: 期望 %d, 实际 %d',
            $expected,
            $actual
        ));
    }

    /**
     * 创建一个表示时序同步错误的异常
     *
     * @param string $componentName 发生错误的组件名称
     * @return self
     */
    public static function syncError(string $componentName): self
    {
        return new self(sprintf(
            '组件 %s 的时序同步错误',
            $componentName
        ));
    }
} 