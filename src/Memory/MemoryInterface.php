<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Memory;

/**
 * 内存接口
 * 
 * 定义内存组件必须实现的功能
 */
interface MemoryInterface
{
    /**
     * 从内存指定地址读取一个字节数据
     *
     * @param int $address 要读取的地址
     * @return int 读取的数据（0-255）
     */
    public function read(int $address): int;

    /**
     * 向内存指定地址写入一个字节数据
     *
     * @param int $address 要写入的地址
     * @param int $value 要写入的数据值（0-255）
     * @return void
     */
    public function write(int $address, int $value): void;

    /**
     * 获取内存大小（字节数）
     *
     * @return int 内存大小
     */
    public function getSize(): int;
} 