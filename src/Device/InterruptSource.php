<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Device;

/**
 * 中断源接口
 * 
 * 定义可产生中断的设备必须实现的功能
 */
interface InterruptSource extends DeviceInterface
{
    /**
     * NMI中断类型常量（不可屏蔽中断）
     */
    public const INTERRUPT_NMI = 1;
    
    /**
     * IRQ中断类型常量（可屏蔽中断）
     */
    public const INTERRUPT_IRQ = 2;
    
    /**
     * RESET中断类型常量（重置）
     */
    public const INTERRUPT_RESET = 3;

    /**
     * 检查设备是否有未处理的中断
     *
     * @return bool 如果有未处理的中断返回true，否则返回false
     */
    public function hasInterrupt(): bool;

    /**
     * 获取中断类型
     *
     * @return int 中断类型，对应接口中定义的中断常量
     */
    public function getInterruptType(): int;

    /**
     * 清除中断标志
     *
     * @return void
     */
    public function clearInterrupt(): void;
} 