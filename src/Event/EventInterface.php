<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Event;

/**
 * 事件接口
 * 
 * 定义NES总线系统中的事件必须实现的功能
 */
interface EventInterface
{
    /**
     * 获取事件名称
     *
     * @return string 事件名称
     */
    public function getName(): string;
    
    /**
     * 获取事件数据载荷
     *
     * @return array 事件数据
     */
    public function getPayload(): array;
    
    /**
     * 获取事件发生的时间戳
     *
     * @return float 微秒级时间戳
     */
    public function getTimestamp(): float;
}
