<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Bus;

use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Device\DeviceInterface;
use Tourze\NES\Bus\Device\InterruptSource;
use Tourze\NES\Bus\Exception\MemoryAccessException;

/**
 * 总线抽象基类
 * 
 * 实现总线的基本功能，包括设备连接、内存读写和中断处理
 */
abstract class AbstractBus implements BusInterface
{
    /**
     * 连接到总线的设备列表
     *
     * @var array 格式：['设备ID' => ['device' => DeviceInterface, 'start' => int, 'end' => int]]
     */
    protected array $devices = [];
    
    /**
     * 中断源设备列表
     *
     * @var InterruptSource[]
     */
    protected array $interruptSources = [];
    
    /**
     * 中断处理器
     *
     * @var array<int, callable> 格式：[中断类型 => 处理器回调函数]
     */
    protected array $interruptHandlers = [];
    
    /**
     * {@inheritdoc}
     */
    public function attachDevice(DeviceInterface $device, int $startAddress, int $endAddress): void
    {
        $deviceId = $device->getBusId();
        
        // 保存设备信息
        $this->devices[$deviceId] = [
            'device' => $device,
            'start' => $startAddress,
            'end' => $endAddress
        ];
        
        // 如果设备实现了InterruptSource接口，则添加到中断源列表
        if ($device instanceof InterruptSource) {
            $this->interruptSources[] = $device;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function detachDevice(DeviceInterface $device): void
    {
        $deviceId = $device->getBusId();
        
        if (isset($this->devices[$deviceId])) {
            unset($this->devices[$deviceId]);
        }
        
        // 从中断源列表中移除
        if ($device instanceof InterruptSource) {
            foreach ($this->interruptSources as $key => $source) {
                if ($source === $device) {
                    unset($this->interruptSources[$key]);
                    break;
                }
            }
            
            // 重建索引
            $this->interruptSources = array_values($this->interruptSources);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function read(int $address): int
    {
        // 查找负责处理该地址的设备
        $device = $this->findDeviceForAddress($address);
        
        if ($device && $device instanceof AddressableDevice) {
            return $device->read($address);
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * {@inheritdoc}
     */
    public function write(int $address, int $value): void
    {
        // 查找负责处理该地址的设备
        $device = $this->findDeviceForAddress($address);
        
        if ($device && $device instanceof AddressableDevice) {
            $device->write($address, $value);
            return;
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * {@inheritdoc}
     */
    public function handleInterrupt(int $type): void
    {
        // 如果有对应类型的中断处理器，则调用它
        if (isset($this->interruptHandlers[$type])) {
            $handler = $this->interruptHandlers[$type];
            $handler();
        }
    }
    
    /**
     * 设置中断处理器
     *
     * @param int $type 中断类型
     * @param callable $handler 处理器回调函数
     * @return void
     */
    public function setInterruptHandler(int $type, callable $handler): void
    {
        $this->interruptHandlers[$type] = $handler;
    }
    
    /**
     * 查找负责处理指定地址的设备
     *
     * @param int $address 内存地址
     * @return DeviceInterface|null 找到的设备，如果没有找到则返回null
     */
    protected function findDeviceForAddress(int $address): ?DeviceInterface
    {
        foreach ($this->devices as $deviceInfo) {
            $start = $deviceInfo['start'];
            $end = $deviceInfo['end'];
            
            if ($address >= $start && $address <= $end) {
                return $deviceInfo['device'];
            }
        }
        
        return null;
    }
    
    /**
     * 检查所有中断源是否有待处理的中断，并处理它们
     *
     * @return void
     */
    protected function checkAndHandleInterrupts(): void
    {
        foreach ($this->interruptSources as $source) {
            if ($source->hasInterrupt()) {
                $type = $source->getInterruptType();
                $this->handleInterrupt($type);
                $source->clearInterrupt();
            }
        }
    }
} 