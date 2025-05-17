<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Exception;

/**
 * 内存访问异常
 * 
 * 表示内存访问过程中发生的异常，如地址越界、只读内存写入等
 */
class MemoryAccessException extends BusException
{
    /**
     * 创建一个表示无效地址的异常
     *
     * @param int $address 尝试访问的无效地址
     * @return self
     */
    public static function invalidAddress(int $address): self
    {
        return new self(sprintf(
            '尝试访问无效的内存地址: 0x%04X',
            $address
        ));
    }

    /**
     * 创建一个表示只读内存写入的异常
     *
     * @param int $address 尝试写入的只读地址
     * @return self
     */
    public static function readOnlyMemory(int $address): self
    {
        return new self(sprintf(
            '尝试写入只读内存地址: 0x%04X',
            $address
        ));
    }

    /**
     * 创建一个表示地址超出范围的异常
     *
     * @param int $address 尝试访问的地址
     * @param int $maxAddress 最大有效地址
     * @return self
     */
    public static function addressOutOfRange(int $address, int $maxAddress): self
    {
        return new self(sprintf(
            '内存地址超出范围: 0x%04X (最大有效地址: 0x%04X)',
            $address,
            $maxAddress
        ));
    }
} 