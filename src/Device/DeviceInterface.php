<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Device;

/**
 * 设备接口
 * 
 * 定义可连接到总线的设备必须实现的功能
 */
interface DeviceInterface
{
    /**
     * 获取设备在总线上的唯一标识
     *
     * @return string 设备ID
     */
    public function getBusId(): string;

    /**
     * 重置设备状态
     *
     * @return void
     */
    public function reset(): void;
} 