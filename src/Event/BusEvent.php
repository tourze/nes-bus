<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Event;

/**
 * 总线事件类
 * 
 * 定义NES总线系统中的各种事件类型和创建方法
 */
class BusEvent implements EventInterface
{
    /**
     * 总线读取事件
     */
    public const EVENT_BUS_READ = 'bus.read';
    
    /**
     * 总线写入事件
     */
    public const EVENT_BUS_WRITE = 'bus.write';
    
    /**
     * 总线中断事件
     */
    public const EVENT_BUS_INTERRUPT = 'bus.interrupt';
    
    /**
     * 设备连接事件
     */
    public const EVENT_DEVICE_ATTACHED = 'device.attached';
    
    /**
     * 设备断开事件
     */
    public const EVENT_DEVICE_DETACHED = 'device.detached';
    
    /**
     * 时钟周期事件
     */
    public const EVENT_CLOCK_TICK = 'clock.tick';
    
    /**
     * 事件名称
     * 
     * @var string
     */
    private string $name;
    
    /**
     * 事件载荷数据
     * 
     * @var array
     */
    private array $payload;
    
    /**
     * 事件发生时间戳
     * 
     * @var float
     */
    private float $timestamp;
    
    /**
     * 构造函数
     * 
     * @param string $name 事件名称
     * @param array $payload 事件数据
     */
    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
        $this->timestamp = microtime(true);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
    
    /**
     * 创建总线读取事件
     * 
     * @param int $address 读取的地址
     * @param int $value 读取的值
     * @return self
     */
    public static function createReadEvent(int $address, int $value): self
    {
        return new self(self::EVENT_BUS_READ, [
            'address' => $address,
            'value' => $value
        ]);
    }
    
    /**
     * 创建总线写入事件
     * 
     * @param int $address 写入的地址
     * @param int $value 写入的值
     * @return self
     */
    public static function createWriteEvent(int $address, int $value): self
    {
        return new self(self::EVENT_BUS_WRITE, [
            'address' => $address,
            'value' => $value
        ]);
    }
    
    /**
     * 创建总线中断事件
     * 
     * @param int $interruptType 中断类型
     * @param string $source 中断源
     * @return self
     */
    public static function createInterruptEvent(int $interruptType, string $source): self
    {
        return new self(self::EVENT_BUS_INTERRUPT, [
            'interruptType' => $interruptType,
            'source' => $source
        ]);
    }
    
    /**
     * 创建设备连接事件
     * 
     * @param string $deviceId 设备ID
     * @param int $startAddress 起始地址
     * @param int $endAddress 结束地址
     * @return self
     */
    public static function createDeviceAttachedEvent(string $deviceId, int $startAddress, int $endAddress): self
    {
        return new self(self::EVENT_DEVICE_ATTACHED, [
            'deviceId' => $deviceId,
            'startAddress' => $startAddress,
            'endAddress' => $endAddress
        ]);
    }
    
    /**
     * 创建设备断开事件
     * 
     * @param string $deviceId 设备ID
     * @return self
     */
    public static function createDeviceDetachedEvent(string $deviceId): self
    {
        return new self(self::EVENT_DEVICE_DETACHED, [
            'deviceId' => $deviceId
        ]);
    }
    
    /**
     * 创建时钟周期事件
     * 
     * @param int $cycleCount 周期计数
     * @return self
     */
    public static function createClockTickEvent(int $cycleCount): self
    {
        return new self(self::EVENT_CLOCK_TICK, [
            'cycleCount' => $cycleCount
        ]);
    }
}
