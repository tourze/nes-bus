<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Memory;

use Tourze\NES\Bus\Exception\MemoryAccessException;

/**
 * 内存映射管理类
 * 
 * 管理内存地址映射、镜像和只读/可写区域
 */
class MemoryMap
{
    /**
     * 内存映射区域
     * 
     * @var array
     */
    private array $regions = [];

    /**
     * 添加内存区域
     *
     * @param int $startAddress 起始地址
     * @param int $endAddress 结束地址
     * @param callable $readCallback 读取回调函数，参数：(int $address)，返回：int
     * @param callable $writeCallback 写入回调函数，参数：(int $address, int $value)
     * @param callable|null $mapperCallback 地址映射回调函数，参数：(int $address)，返回：int
     * @param int $priority 优先级，数字越大优先级越高
     * @return void
     */
    public function addRegion(
        int $startAddress,
        int $endAddress,
        callable $readCallback,
        callable $writeCallback,
        ?callable $mapperCallback = null,
        int $priority = 0
    ): void {
        $this->regions[] = [
            'start' => $startAddress,
            'end' => $endAddress,
            'read' => $readCallback,
            'write' => $writeCallback,
            'mapper' => $mapperCallback,
            'priority' => $priority,
            'readOnly' => false,
        ];
        
        // 按优先级排序，高优先级在前
        usort($this->regions, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
    }
    
    /**
     * 添加只读内存区域
     *
     * @param int $startAddress 起始地址
     * @param int $endAddress 结束地址
     * @param callable $readCallback 读取回调函数，参数：(int $address)，返回：int
     * @param callable|null $mapperCallback 地址映射回调函数，参数：(int $address)，返回：int
     * @param int $priority 优先级，数字越大优先级越高
     * @return void
     */
    public function addReadOnlyRegion(
        int $startAddress,
        int $endAddress,
        callable $readCallback,
        ?callable $mapperCallback = null,
        int $priority = 0
    ): void {
        $this->regions[] = [
            'start' => $startAddress,
            'end' => $endAddress,
            'read' => $readCallback,
            'write' => null, // 没有写入回调
            'mapper' => $mapperCallback,
            'priority' => $priority,
            'readOnly' => true,
        ];
        
        // 按优先级排序，高优先级在前
        usort($this->regions, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
    }
    
    /**
     * 从内存中读取数据
     *
     * @param int $address 内存地址
     * @return int 读取的值（0-255）
     * @throws MemoryAccessException 如果地址未映射或读取失败
     */
    public function read(int $address): int
    {
        foreach ($this->regions as $region) {
            if ($address >= $region['start'] && $address <= $region['end']) {
                // 如果有映射函数，应用它
                if ($region['mapper'] !== null) {
                    $mappedAddress = ($region['mapper'])($address);
                    
                    // 尝试使用映射后的地址读取
                    $result = $this->readFromMappedAddress($mappedAddress);
                    if ($result !== null) {
                        return $result;
                    }
                }
                
                // 使用此区域的读取回调
                $value = ($region['read'])($address);
                
                // 确保返回值在0-255范围内
                return $value & 0xFF;
            }
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 向内存写入数据
     *
     * @param int $address 内存地址
     * @param int $value 要写入的值（0-255）
     * @throws MemoryAccessException 如果地址未映射、只读或写入失败
     * @return void
     */
    public function write(int $address, int $value): void
    {
        // 确保值在0-255范围内
        $value = $value & 0xFF;
        
        foreach ($this->regions as $region) {
            if ($address >= $region['start'] && $address <= $region['end']) {
                // 检查区域是否为只读
                if ($region['readOnly']) {
                    throw MemoryAccessException::readOnlyMemory($address);
                }
                
                // 如果有映射函数，应用它
                if ($region['mapper'] !== null) {
                    $mappedAddress = ($region['mapper'])($address);
                    
                    // 尝试使用映射后的地址写入
                    if ($this->writeToMappedAddress($mappedAddress, $value)) {
                        return;
                    }
                }
                
                // 使用此区域的写入回调
                ($region['write'])($address, $value);
                return;
            }
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 从映射后的地址读取数据
     *
     * @param int $mappedAddress 映射后的地址
     * @return int|null 读取的值，如果没有找到适合的区域则返回null
     */
    private function readFromMappedAddress(int $mappedAddress): ?int
    {
        foreach ($this->regions as $region) {
            if ($mappedAddress >= $region['start'] && $mappedAddress <= $region['end']) {
                // 使用此区域的读取回调
                $value = ($region['read'])($mappedAddress);
                
                // 确保返回值在0-255范围内
                return $value & 0xFF;
            }
        }
        
        return null;
    }
    
    /**
     * 向映射后的地址写入数据
     *
     * @param int $mappedAddress 映射后的地址
     * @param int $value 要写入的值
     * @return bool 是否成功写入
     */
    private function writeToMappedAddress(int $mappedAddress, int $value): bool
    {
        foreach ($this->regions as $region) {
            if ($mappedAddress >= $region['start'] && $mappedAddress <= $region['end']) {
                // 检查区域是否为只读
                if ($region['readOnly']) {
                    return false;
                }
                
                // 使用此区域的写入回调
                ($region['write'])($mappedAddress, $value);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 清除所有内存映射区域
     *
     * @return void
     */
    public function clearRegions(): void
    {
        $this->regions = [];
    }
    
    /**
     * 获取所有映射区域
     *
     * @return array 区域列表
     */
    public function getRegions(): array
    {
        return $this->regions;
    }
}
