<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Event\BusEvent;
use Tourze\NES\Bus\Event\EventInterface;

class BusEventTest extends TestCase
{
    /**
     * 测试创建基本的总线事件
     */
    public function test_create_bus_event(): void
    {
        $event = new BusEvent('test.event', ['data' => 'value']);
        
        $this->assertInstanceOf(EventInterface::class, $event);
        $this->assertEquals('test.event', $event->getName());
        $this->assertEquals(['data' => 'value'], $event->getPayload());
        $this->assertIsFloat($event->getTimestamp());
    }
    
    /**
     * 测试创建读取事件
     */
    public function test_create_read_event(): void
    {
        $event = BusEvent::createReadEvent(0x1234, 0x42);
        
        $this->assertEquals(BusEvent::EVENT_BUS_READ, $event->getName());
        $this->assertEquals([
            'address' => 0x1234,
            'value' => 0x42
        ], $event->getPayload());
    }
    
    /**
     * 测试创建写入事件
     */
    public function test_create_write_event(): void
    {
        $event = BusEvent::createWriteEvent(0x1234, 0x42);
        
        $this->assertEquals(BusEvent::EVENT_BUS_WRITE, $event->getName());
        $this->assertEquals([
            'address' => 0x1234,
            'value' => 0x42
        ], $event->getPayload());
    }
    
    /**
     * 测试创建中断事件
     */
    public function test_create_interrupt_event(): void
    {
        $event = BusEvent::createInterruptEvent(1, 'test-device');
        
        $this->assertEquals(BusEvent::EVENT_BUS_INTERRUPT, $event->getName());
        $this->assertEquals([
            'interruptType' => 1,
            'source' => 'test-device'
        ], $event->getPayload());
    }
    
    /**
     * 测试创建设备连接事件
     */
    public function test_create_device_attached_event(): void
    {
        $event = BusEvent::createDeviceAttachedEvent('test-device', 0x0000, 0x1FFF);
        
        $this->assertEquals(BusEvent::EVENT_DEVICE_ATTACHED, $event->getName());
        $this->assertEquals([
            'deviceId' => 'test-device',
            'startAddress' => 0x0000,
            'endAddress' => 0x1FFF
        ], $event->getPayload());
    }
    
    /**
     * 测试创建设备断开事件
     */
    public function test_create_device_detached_event(): void
    {
        $event = BusEvent::createDeviceDetachedEvent('test-device');
        
        $this->assertEquals(BusEvent::EVENT_DEVICE_DETACHED, $event->getName());
        $this->assertEquals([
            'deviceId' => 'test-device'
        ], $event->getPayload());
    }
    
    /**
     * 测试创建时钟周期事件
     */
    public function test_create_clock_tick_event(): void
    {
        $event = BusEvent::createClockTickEvent(123);
        
        $this->assertEquals(BusEvent::EVENT_CLOCK_TICK, $event->getName());
        $this->assertEquals([
            'cycleCount' => 123
        ], $event->getPayload());
    }
}
