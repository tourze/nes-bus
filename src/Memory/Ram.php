<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Memory;

use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Exception\MemoryAccessException;

/**
 * RAM内存类
 * 
 * 实现基本的内存读写操作
 */
class Ram implements MemoryInterface, AddressableDevice
{
    /**
     * 设备ID
     *
     * @var string
     */
    private string $busId;
    
    /**
     * 内存大小（字节数）
     *
     * @var int
     */
    private int $size;
    
    /**
     * 内存数据
     *
     * @var array<int, int>
     */
    private array $data = [];
    
    /**
     * 构造函数
     *
     * @param string $busId 设备ID
     * @param int $size 内存大小（字节数）
     * @param array<int, int> $initialData 初始数据（可选）
     */
    public function __construct(string $busId, int $size, array $initialData = [])
    {
        $this->busId = $busId;
        $this->size = $size;
        
        // 初始化内存为0
        $this->reset();
        
        // 如果提供了初始数据，则写入内存
        foreach ($initialData as $address => $value) {
            if ($address >= 0 && $address < $size) {
                $this->data[$address] = $value & 0xFF; // 确保值在0-255范围内
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBusId(): string
    {
        return $this->busId;
    }
    
    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        // 将所有内存初始化为0
        $this->data = array_fill(0, $this->size, 0);
    }
    
    /**
     * {@inheritdoc}
     */
    public function read(int $address): int
    {
        $this->validateAddress($address);
        return $this->data[$address];
    }
    
    /**
     * {@inheritdoc}
     */
    public function write(int $address, int $value): void
    {
        $this->validateAddress($address);
        
        // 确保值在0-255范围内
        if ($value < 0) {
            $value = 0;
        } elseif ($value > 255) {
            $value = $value % 256;
        }
        
        $this->data[$address] = $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAddressRange(): array
    {
        return [
            'start' => 0,
            'end' => $this->size - 1
        ];
    }
    
    /**
     * 验证地址是否在有效范围内
     *
     * @param int $address 要验证的地址
     * @throws MemoryAccessException 如果地址无效
     */
    private function validateAddress(int $address): void
    {
        if ($address < 0 || $address >= $this->size) {
            throw MemoryAccessException::addressOutOfRange($address, $this->size - 1);
        }
    }
} 