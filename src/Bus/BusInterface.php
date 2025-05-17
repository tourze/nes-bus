<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Bus;

use Tourze\NES\Bus\Device\DeviceInterface;

/**
 * 总线接口
 * 
 * 定义NES系统总线的基本操作，包括读写数据和设备连接
 */
interface BusInterface
{
    /**
     * 从总线上指定地址读取一个字节数据
     *
     * @param int $address 要读取的地址
     * @return int 读取的数据（0-255）
     */
    public function read(int $address): int;

    /**
     * 向总线上指定地址写入一个字节数据
     *
     * @param int $address 要写入的地址
     * @param int $value 要写入的数据值（0-255）
     * @return void
     */
    public function write(int $address, int $value): void;

    /**
     * 将设备连接到总线上
     *
     * @param DeviceInterface $device 要连接的设备
     * @param int $startAddress 设备在总线上的起始地址
     * @param int $endAddress 设备在总线上的结束地址
     * @return void
     */
    public function attachDevice(DeviceInterface $device, int $startAddress, int $endAddress): void;

    /**
     * 从总线上断开设备
     *
     * @param DeviceInterface $device 要断开的设备
     * @return void
     */
    public function detachDevice(DeviceInterface $device): void;

    /**
     * 处理中断信号
     *
     * @param int $type 中断类型
     * @return void
     */
    public function handleInterrupt(int $type): void;
} 