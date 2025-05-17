<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Bus;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Bus\CpuBus;
use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Memory\Ram;

class CpuBusTest extends TestCase
{
    /**
     * 测试CPU总线基本创建
     */
    public function test_cpu_bus_creation(): void
    {
        $cpuBus = new CpuBus();
        $this->assertInstanceOf(CpuBus::class, $cpuBus);
    }
    
    /**
     * 测试RAM连接和读写
     */
    public function test_ram_read_write(): void
    {
        $cpuBus = new CpuBus();
        $ram = new Ram('ram', 0x0800); // 2KB内部RAM
        
        $cpuBus->attachDevice($ram, 0x0000, 0x07FF);
        
        // 写入然后读取
        $cpuBus->write(0x0000, 0x42);
        $cpuBus->write(0x0001, 0xFF);
        $cpuBus->write(0x07FF, 0x55);
        
        $this->assertEquals(0x42, $cpuBus->read(0x0000));
        $this->assertEquals(0xFF, $cpuBus->read(0x0001));
        $this->assertEquals(0x55, $cpuBus->read(0x07FF));
    }
    
    /**
     * 测试RAM镜像
     */
    public function test_ram_mirroring(): void
    {
        $cpuBus = new CpuBus();
        $ram = new Ram('ram', 0x0800); // 2KB内部RAM
        
        $cpuBus->attachDevice($ram, 0x0000, 0x07FF);
        
        // 启用RAM镜像
        $cpuBus->enableRamMirroring();
        
        // 写入原始RAM
        $cpuBus->write(0x0000, 0x42);
        $cpuBus->write(0x0100, 0xFF);
        
        // 从镜像区域读取
        $this->assertEquals(0x42, $cpuBus->read(0x0000 + 0x0800)); // 第一个镜像
        $this->assertEquals(0x42, $cpuBus->read(0x0000 + 0x1000)); // 第二个镜像
        $this->assertEquals(0x42, $cpuBus->read(0x0000 + 0x1800)); // 第三个镜像
        
        $this->assertEquals(0xFF, $cpuBus->read(0x0100 + 0x0800)); // 第一个镜像
        $this->assertEquals(0xFF, $cpuBus->read(0x0100 + 0x1000)); // 第二个镜像
        $this->assertEquals(0xFF, $cpuBus->read(0x0100 + 0x1800)); // 第三个镜像
        
        // 写入镜像区域，然后从原始RAM读取
        $cpuBus->write(0x0200 + 0x0800, 0x55); // 写入第一个镜像
        $this->assertEquals(0x55, $cpuBus->read(0x0200)); // 从原始区域读取
        
        $cpuBus->write(0x0300 + 0x1000, 0xAA); // 写入第二个镜像
        $this->assertEquals(0xAA, $cpuBus->read(0x0300)); // 从原始区域读取
    }
    
    /**
     * 测试IO寄存器的连接和访问
     */
    public function test_io_registers(): void
    {
        $cpuBus = new CpuBus();
        
        // 模拟PPU寄存器
        $ppuRegisters = $this->createMock(AddressableDevice::class);
        $ppuRegisters->method('getBusId')->willReturn('ppu-registers');
        $ppuRegisters->method('getAddressRange')->willReturn(['start' => 0x2000, 'end' => 0x2007]);
        
        // 期望写入方法被调用
        $ppuRegisters->expects($this->once())
            ->method('write')
            ->with($this->equalTo(0x2000), $this->equalTo(0x42));
        
        // 期望读取方法被调用
        $ppuRegisters->expects($this->once())
            ->method('read')
            ->with($this->equalTo(0x2000))
            ->willReturn(0x42);
        
        $cpuBus->attachDevice($ppuRegisters, 0x2000, 0x2007);
        
        // 访问PPU寄存器
        $cpuBus->write(0x2000, 0x42);
        $value = $cpuBus->read(0x2000);
        
        $this->assertEquals(0x42, $value);
    }
    
    /**
     * 测试PPU寄存器镜像
     */
    public function test_ppu_register_mirroring(): void
    {
        $cpuBus = new CpuBus();
        
        // 模拟PPU寄存器
        $ppuRegisters = $this->createMock(AddressableDevice::class);
        $ppuRegisters->method('getBusId')->willReturn('ppu-registers');
        $ppuRegisters->method('getAddressRange')->willReturn(['start' => 0x2000, 'end' => 0x2007]);
        
        // 设置期望：当访问镜像地址0x2008时，实际应该访问0x2000
        $ppuRegisters->expects($this->once())
            ->method('read')
            ->with($this->equalTo(0x2000)) // 实际应该访问的是0x2000
            ->willReturn(0x42);
        
        $cpuBus->attachDevice($ppuRegisters, 0x2000, 0x2007);
        
        // 启用PPU寄存器镜像
        $cpuBus->enablePpuRegisterMirroring();
        
        // 从镜像地址读取
        $value = $cpuBus->read(0x2008); // 这应该映射到0x2000
        
        $this->assertEquals(0x42, $value);
    }
    
    /**
     * 测试卡带空间的连接和访问
     */
    public function test_cartridge_space(): void
    {
        $cpuBus = new CpuBus();
        
        // 模拟PRG ROM
        $prgRom = $this->createMock(AddressableDevice::class);
        $prgRom->method('getBusId')->willReturn('prg-rom');
        $prgRom->method('getAddressRange')->willReturn(['start' => 0x8000, 'end' => 0xFFFF]);
        
        // 模拟从PRG ROM读取数据
        $prgRom->method('read')
            ->willReturnCallback(function ($address) {
                // 简单的返回地址的低8位作为数据
                return $address & 0xFF;
            });
        
        $cpuBus->attachDevice($prgRom, 0x8000, 0xFFFF);
        
        // 读取PRG ROM数据
        $this->assertEquals(0x00, $cpuBus->read(0x8000));
        $this->assertEquals(0xFF, $cpuBus->read(0x80FF));
        $this->assertEquals(0x55, $cpuBus->read(0xFF55));
    }
    
    /**
     * 测试NMI中断处理
     */
    public function test_nmi_interrupt_handling(): void
    {
        $cpuBus = new CpuBus();
        
        // 设置NMI中断处理器
        $nmiHandled = false;
        $cpuBus->setNmiHandler(function () use (&$nmiHandled) {
            $nmiHandled = true;
        });
        
        // 触发NMI中断
        $cpuBus->triggerNmi();
        
        $this->assertTrue($nmiHandled, 'NMI中断应该被处理');
    }
    
    /**
     * 测试IRQ中断处理
     */
    public function test_irq_interrupt_handling(): void
    {
        $cpuBus = new CpuBus();
        
        // 设置IRQ中断处理器
        $irqHandled = false;
        $cpuBus->setIrqHandler(function () use (&$irqHandled) {
            $irqHandled = true;
        });
        
        // 触发IRQ中断
        $cpuBus->triggerIrq();
        
        $this->assertTrue($irqHandled, 'IRQ中断应该被处理');
    }
} 