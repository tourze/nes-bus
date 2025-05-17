<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Exception\MemoryAccessException;
use Tourze\NES\Bus\Memory\MemoryMap;

class MemoryMapTest extends TestCase
{
    /**
     * 测试创建MemoryMap实例
     */
    public function test_create_memory_map(): void
    {
        $memoryMap = new MemoryMap();
        $this->assertInstanceOf(MemoryMap::class, $memoryMap);
    }
    
    /**
     * 测试添加和管理内存区域
     */
    public function test_add_memory_region(): void
    {
        $memoryMap = new MemoryMap();
        
        // 添加一个内存区域
        $memoryMap->addRegion(0x0000, 0x1FFF, function(int $address): int {
            return 0x42; // 总是返回固定值用于测试
        }, function(int $address, int $value): void {
            // 写入回调，这里不需要实际操作
        });
        
        // 验证读取操作使用了正确的回调
        $this->assertEquals(0x42, $memoryMap->read(0x0000));
        $this->assertEquals(0x42, $memoryMap->read(0x1000));
        $this->assertEquals(0x42, $memoryMap->read(0x1FFF));
    }
    
    /**
     * 测试映射函数转换内存地址
     */
    public function test_map_address_with_function(): void
    {
        $memoryMap = new MemoryMap();
        
        // 添加一个带映射函数的区域，将地址映射到高1KB区域
        $memoryMap->addRegion(
            0x0000, 
            0x03FF, 
            function(int $address): int {
                // 基于地址返回不同的值
                return $address & 0xFF;
            },
            function(int $address, int $value): void {
                // 写入回调
            },
            function(int $address): int {
                // 映射到0x0400-0x07FF区域
                return 0x0400 + ($address & 0x03FF);
            }
        );
        
        // 添加目标区域
        $mockMemory = [];
        $memoryMap->addRegion(
            0x0400, 
            0x07FF, 
            function(int $address) use (&$mockMemory): int {
                return $mockMemory[$address] ?? 0;
            },
            function(int $address, int $value) use (&$mockMemory): void {
                $mockMemory[$address] = $value;
            }
        );
        
        // 写入原始区域
        $memoryMap->write(0x0000, 0x42);
        
        // 验证数据被映射写入到目标区域
        $this->assertEquals(0x42, $mockMemory[0x0400]);
        
        // 从目标区域读取应该成功
        $mockMemory[0x0500] = 0xFF;
        $this->assertEquals(0xFF, $memoryMap->read(0x0100));
    }
    
    /**
     * 测试添加只读区域
     */
    public function test_add_read_only_region(): void
    {
        $memoryMap = new MemoryMap();
        
        // 添加一个只读区域
        $memoryMap->addReadOnlyRegion(0x8000, 0x9FFF, function(int $address): int {
            return 0x55; // 固定返回值
        });
        
        // 读取应该成功
        $this->assertEquals(0x55, $memoryMap->read(0x8000));
        $this->assertEquals(0x55, $memoryMap->read(0x9FFF));
        
        // 写入应该抛出异常
        $this->expectException(MemoryAccessException::class);
        $memoryMap->write(0x8000, 0x42);
    }
    
    /**
     * 测试访问未映射区域时抛出异常
     */
    public function test_access_unmapped_region_throws_exception(): void
    {
        $memoryMap = new MemoryMap();
        
        // 读取未映射区域
        $this->expectException(MemoryAccessException::class);
        $memoryMap->read(0x0000);
    }
    
    /**
     * 测试多个重叠区域的优先级
     */
    public function test_overlapping_regions_priority(): void
    {
        $memoryMap = new MemoryMap();
        
        // 添加低优先级区域（覆盖整个范围）
        $memoryMap->addRegion(0x0000, 0x1FFF, function(int $address): int {
            return 0x11;
        }, function(int $address, int $value): void {
            // 写入回调
        }, null, 0);
        
        // 添加高优先级区域（只覆盖一部分）
        $memoryMap->addRegion(0x1000, 0x1FFF, function(int $address): int {
            return 0x22;
        }, function(int $address, int $value): void {
            // 写入回调
        }, null, 1);
        
        // 低地址应该读取低优先级区域
        $this->assertEquals(0x11, $memoryMap->read(0x0500));
        
        // 高地址应该读取高优先级区域
        $this->assertEquals(0x22, $memoryMap->read(0x1500));
    }
    
    /**
     * 测试映射相同区域的多个副本（镜像）
     */
    public function test_mirroring_same_region(): void
    {
        $memoryMap = new MemoryMap();
        $data = array_fill(0, 0x0400, 0);
        
        // 添加一个基础区域
        $memoryMap->addRegion(
            0x0000, 
            0x03FF, 
            function(int $address) use (&$data): int {
                return $data[$address & 0x03FF];
            },
            function(int $address, int $value) use (&$data): void {
                $data[$address & 0x03FF] = $value;
            }
        );
        
        // 镜像到其他区域
        for ($i = 1; $i < 4; $i++) {
            $startAddress = $i * 0x0400;
            $endAddress = $startAddress + 0x03FF;
            
            $memoryMap->addRegion(
                $startAddress, 
                $endAddress, 
                function(int $address) use (&$data): int {
                    return $data[$address & 0x03FF];
                },
                function(int $address, int $value) use (&$data): void {
                    $data[$address & 0x03FF] = $value;
                }
            );
        }
        
        // 写入第一个区域
        $memoryMap->write(0x0123, 0x42);
        
        // 从其他区域读取应该得到相同的值
        $this->assertEquals(0x42, $memoryMap->read(0x0123)); // 原始区域
        $this->assertEquals(0x42, $memoryMap->read(0x0123 + 0x0400)); // 第一个镜像
        $this->assertEquals(0x42, $memoryMap->read(0x0123 + 0x0800)); // 第二个镜像
        $this->assertEquals(0x42, $memoryMap->read(0x0123 + 0x0C00)); // 第三个镜像
        
        // 写入镜像区域
        $memoryMap->write(0x0123 + 0x0800, 0x55);
        
        // 所有区域应该更新
        $this->assertEquals(0x55, $memoryMap->read(0x0123)); // 原始区域
        $this->assertEquals(0x55, $memoryMap->read(0x0123 + 0x0400)); // 第一个镜像
        $this->assertEquals(0x55, $memoryMap->read(0x0123 + 0x0800)); // 第二个镜像
        $this->assertEquals(0x55, $memoryMap->read(0x0123 + 0x0C00)); // 第三个镜像
    }
    
    /**
     * 测试清除区域
     */
    public function test_clear_regions(): void
    {
        $memoryMap = new MemoryMap();
        
        // 添加一个内存区域
        $memoryMap->addRegion(0x0000, 0x1FFF, function(int $address): int {
            return 0x42;
        }, function(int $address, int $value): void {
            // 写入回调
        });
        
        // 验证区域已添加
        $this->assertEquals(0x42, $memoryMap->read(0x1000));
        
        // 清除所有区域
        $memoryMap->clearRegions();
        
        // 验证区域已清除
        $this->expectException(MemoryAccessException::class);
        $memoryMap->read(0x1000);
    }
}
