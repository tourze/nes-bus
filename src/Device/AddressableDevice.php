<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Device;

/**
 * 可寻址设备接口
 * 
 * 定义可寻址设备必须实现的功能，这类设备可以在特定地址范围内进行读写操作
 */
interface AddressableDevice extends DeviceInterface
{
    /**
     * 从设备指定地址读取一个字节数据
     *
     * @param int $address 要读取的地址
     * @return int 读取的数据（0-255）
     */
    public function read(int $address): int;

    /**
     * 向设备指定地址写入一个字节数据
     *
     * @param int $address 要写入的地址
     * @param int $value 要写入的数据值（0-255）
     * @return void
     */
    public function write(int $address, int $value): void;

    /**
     * 获取设备的地址范围
     *
     * @return array 包含起始地址和结束地址的数组，格式为 ['start' => int, 'end' => int]
     */
    public function getAddressRange(): array;
}
