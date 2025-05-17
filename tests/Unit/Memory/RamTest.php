<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Device\DeviceInterface;
use Tourze\NES\Bus\Exception\MemoryAccessException;
use Tourze\NES\Bus\Memory\MemoryInterface;
use Tourze\NES\Bus\Memory\Ram;

class RamTest extends TestCase
{
    /**
     * 测试RAM基本属性
     */
    public function test_ram_basic_properties(): void
    {
        $ram = new Ram('test-ram', 0x1000); // 4KB RAM
        
        $this->assertInstanceOf(MemoryInterface::class, $ram);
        $this->assertInstanceOf(DeviceInterface::class, $ram);
        $this->assertEquals(0x1000, $ram->getSize());
        $this->assertEquals('test-ram', $ram->getBusId());
    }
    
    /**
     * 测试初始化RAM后内容默认为0
     */
    public function test_ram_initializes_to_zero(): void
    {
        $ram = new Ram('ram', 0x100);
        
        for ($i = 0; $i < 0x100; $i++) {
            $this->assertEquals(0, $ram->read($i));
        }
    }
    
    /**
     * 测试RAM写入然后读取
     */
    public function test_ram_write_then_read(): void
    {
        $ram = new Ram('ram', 0x100);
        
        $ram->write(0x00, 0x42);
        $ram->write(0x01, 0xFF);
        $ram->write(0xFF, 0x55);
        
        $this->assertEquals(0x42, $ram->read(0x00));
        $this->assertEquals(0xFF, $ram->read(0x01));
        $this->assertEquals(0x55, $ram->read(0xFF));
        $this->assertEquals(0x00, $ram->read(0x02)); // 未写入的地址仍为0
    }
    
    /**
     * 测试写入值自动限制在0-255范围内
     */
    public function test_ram_values_are_clamped(): void
    {
        $ram = new Ram('ram', 0x100);
        
        $ram->write(0x10, -5); // 应该被截断为0
        $ram->write(0x11, 300); // 应该被截断为44 (300 % 256)
        
        $this->assertEquals(0, $ram->read(0x10));
        $this->assertEquals(300 % 256, $ram->read(0x11));
    }
    
    /**
     * 测试访问超出范围的地址时抛出异常
     */
    public function test_ram_access_beyond_size_throws_exception(): void
    {
        $ram = new Ram('ram', 0x100); // 256字节RAM
        
        // 读取超出范围的地址
        $this->expectException(MemoryAccessException::class);
        $ram->read(0x100); // 地址范围应该是0x00-0xFF
    }
    
    /**
     * 测试写入超出范围的地址时抛出异常
     */
    public function test_ram_write_beyond_size_throws_exception(): void
    {
        $ram = new Ram('ram', 0x100); // 256字节RAM
        
        // 写入超出范围的地址
        $this->expectException(MemoryAccessException::class);
        $ram->write(0x100, 0x42); // 地址范围应该是0x00-0xFF
    }
    
    /**
     * 测试重置功能
     */
    public function test_ram_reset(): void
    {
        $ram = new Ram('ram', 0x100);
        
        // 写入一些值
        $ram->write(0x00, 0x42);
        $ram->write(0x01, 0xFF);
        
        // 确认值被写入
        $this->assertEquals(0x42, $ram->read(0x00));
        $this->assertEquals(0xFF, $ram->read(0x01));
        
        // 重置RAM
        $ram->reset();
        
        // 验证所有值被清零
        $this->assertEquals(0x00, $ram->read(0x00));
        $this->assertEquals(0x00, $ram->read(0x01));
    }
    
    /**
     * 测试初始化RAM并指定初始内容
     */
    public function test_ram_initialize_with_data(): void
    {
        $initialData = [
            0x00 => 0x42,
            0x01 => 0xFF,
            0x02 => 0x55
        ];
        
        $ram = new Ram('ram', 0x100, $initialData);
        
        $this->assertEquals(0x42, $ram->read(0x00));
        $this->assertEquals(0xFF, $ram->read(0x01));
        $this->assertEquals(0x55, $ram->read(0x02));
        $this->assertEquals(0x00, $ram->read(0x03)); // 未指定的地址应为0
    }
} 