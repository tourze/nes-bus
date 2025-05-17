<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Bus;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Bus\AbstractBus;
use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Device\DeviceInterface;
use Tourze\NES\Bus\Device\InterruptSource;
use Tourze\NES\Bus\Exception\MemoryAccessException;

class AbstractBusTest extends TestCase
{
    /**
     * 创建一个具体的AbstractBus子类用于测试
     */
    private function createBus(): AbstractBus
    {
        return new class extends AbstractBus {
            public function getDevices(): array
            {
                return $this->devices;
            }
            
            public function getInterruptSources(): array
            {
                return $this->interruptSources;
            }
        };
    }
    
    /**
     * 创建一个模拟的可寻址设备用于测试
     * 
     * @param string $id 设备ID
     * @param int $startAddress 起始地址
     * @param int $endAddress 结束地址
     * @param array $memory 内存内容
     * @return AddressableDevice
     */
    private function createMockDevice(
        string $id, 
        int $startAddress, 
        int $endAddress, 
        array $memory = []
    ): AddressableDevice
    {
        $device = $this->createMock(AddressableDevice::class);
        
        $device->method('getBusId')->willReturn($id);
        
        $device->method('getAddressRange')->willReturn([
            'start' => $startAddress,
            'end' => $endAddress
        ]);
        
        $device->method('read')->willReturnCallback(
            function (int $address) use ($memory) {
                return $memory[$address] ?? 0;
            }
        );
        
        return $device;
    }
    
    /**
     * 创建一个模拟的中断源设备
     * 
     * @param string $id 设备ID
     * @param bool $hasInterrupt 是否有中断
     * @param int $interruptType 中断类型
     * @return DeviceInterface&InterruptSource
     */
    private function createMockInterruptSource(
        string $id,
        bool $hasInterrupt = false,
        int $interruptType = InterruptSource::INTERRUPT_IRQ
    ): DeviceInterface
    {
        // 创建同时实现DeviceInterface和InterruptSource的模拟对象
        $device = $this->getMockBuilder(InterruptSource::class)
                      ->getMock();
        
        $device->method('getBusId')->willReturn($id);
        $device->expects($this->any())
            ->method('reset')
            ->willReturnCallback(function (): void {
                // 不返回任何值
            });
        $device->method('hasInterrupt')->willReturn($hasInterrupt);
        $device->method('getInterruptType')->willReturn($interruptType);
        
        return $device;
    }
    
    /**
     * 测试设备连接功能
     */
    public function test_attach_device(): void
    {
        $bus = $this->createBus();
        $device = $this->createMockDevice('test-device', 0x0000, 0x1FFF);
        
        $bus->attachDevice($device, 0x0000, 0x1FFF);
        
        $devices = $bus->getDevices();
        $this->assertCount(1, $devices);
        $this->assertArrayHasKey('test-device', $devices);
        $this->assertSame($device, $devices['test-device']['device']);
        $this->assertEquals(0x0000, $devices['test-device']['start']);
        $this->assertEquals(0x1FFF, $devices['test-device']['end']);
    }
    
    /**
     * 测试设备断开功能
     */
    public function test_detach_device(): void
    {
        $bus = $this->createBus();
        $device1 = $this->createMockDevice('device-1', 0x0000, 0x1FFF);
        $device2 = $this->createMockDevice('device-2', 0x2000, 0x3FFF);
        
        $bus->attachDevice($device1, 0x0000, 0x1FFF);
        $bus->attachDevice($device2, 0x2000, 0x3FFF);
        
        $this->assertCount(2, $bus->getDevices());
        
        $bus->detachDevice($device1);
        
        $devices = $bus->getDevices();
        $this->assertCount(1, $devices);
        $this->assertArrayNotHasKey('device-1', $devices);
        $this->assertArrayHasKey('device-2', $devices);
    }
    
    /**
     * 测试读取操作
     */
    public function test_read(): void
    {
        $bus = $this->createBus();
        $memory = [
            0x0000 => 0x42,
            0x1000 => 0xFF,
            0x1FFF => 0x55
        ];
        
        $device = $this->createMockDevice('ram', 0x0000, 0x1FFF, $memory);
        $bus->attachDevice($device, 0x0000, 0x1FFF);
        
        $this->assertEquals(0x42, $bus->read(0x0000));
        $this->assertEquals(0xFF, $bus->read(0x1000));
        $this->assertEquals(0x55, $bus->read(0x1FFF));
        $this->assertEquals(0x00, $bus->read(0x1001)); // 未设置的地址返回0
    }
    
    /**
     * 测试读取未映射地址时抛出异常
     */
    public function test_read_unmapped_address_throws_exception(): void
    {
        $bus = $this->createBus();
        $device = $this->createMockDevice('ram', 0x0000, 0x1FFF);
        $bus->attachDevice($device, 0x0000, 0x1FFF);
        
        $this->expectException(MemoryAccessException::class);
        $bus->read(0x2000); // 未映射的地址
    }
    
    /**
     * 测试写入操作被委托给正确的设备
     */
    public function test_write_delegates_to_device(): void
    {
        $bus = $this->createBus();
        
        $device = $this->createMock(AddressableDevice::class);
        $device->method('getBusId')->willReturn('ram');
        $device->method('getAddressRange')->willReturn([
            'start' => 0x0000,
            'end' => 0x1FFF
        ]);
        
        // 断言写方法被调用一次，并且参数正确
        $device->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo(0x1000),
                $this->equalTo(0x42)
            );
        
        $bus->attachDevice($device, 0x0000, 0x1FFF);
        $bus->write(0x1000, 0x42);
    }
    
    /**
     * 测试写入未映射地址时抛出异常
     */
    public function test_write_unmapped_address_throws_exception(): void
    {
        $bus = $this->createBus();
        $device = $this->createMockDevice('ram', 0x0000, 0x1FFF);
        $bus->attachDevice($device, 0x0000, 0x1FFF);
        
        $this->expectException(MemoryAccessException::class);
        $bus->write(0x2000, 0x42); // 未映射的地址
    }
    
    /**
     * 测试处理中断
     */
    public function test_handle_interrupt(): void
    {
        $bus = $this->createBus();
        
        // 创建处理中断的模拟对象
        $mockHandler = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['handleIrq', 'handleNmi', 'handleReset'])
            ->getMock();
        
        // 期望IRQ处理方法被调用一次
        $mockHandler->expects($this->once())->method('handleIrq');
        
        // 设置中断处理器
        $bus->setInterruptHandler(InterruptSource::INTERRUPT_IRQ, function () use ($mockHandler) {
            $mockHandler->handleIrq();
        });
        
        // 处理IRQ中断
        $bus->handleInterrupt(InterruptSource::INTERRUPT_IRQ);
    }
    
    /**
     * 测试添加中断源设备
     */
    public function test_add_interrupt_source(): void
    {
        $bus = $this->createBus();
        $irqSource = $this->createMockInterruptSource('irq-device', true, InterruptSource::INTERRUPT_IRQ);
        
        // 实现了InterruptSource接口的设备会被自动添加到中断源列表
        $bus->attachDevice($irqSource, 0x0000, 0x0000);
        
        $this->assertCount(1, $bus->getInterruptSources());
        $this->assertSame($irqSource, $bus->getInterruptSources()[0]);
    }
} 