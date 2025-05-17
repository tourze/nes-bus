<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Timing;

/**
 * 时钟接口
 * 
 * 定义时钟信号源必须实现的功能
 */
interface ClockInterface
{
    /**
     * 推进一个时钟周期
     *
     * 此方法在每个时钟周期被调用，用于更新时钟状态
     *
     * @return void
     */
    public function tick(): void;

    /**
     * 获取当前累计的时钟周期数
     *
     * @return int 周期数
     */
    public function getCycles(): int;
}
