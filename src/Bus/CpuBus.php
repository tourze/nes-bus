<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Bus;

use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Device\DeviceInterface;
use Tourze\NES\Bus\Device\InterruptSource;
use Tourze\NES\Bus\Exception\MemoryAccessException;

/**
 * CPU总线实现
 * 
 * 实现NES CPU总线的特定功能，如内存镜像和中断处理
 */
class CpuBus extends AbstractBus
{
    /**
     * 内部RAM设备的ID
     */
    private const RAM_DEVICE_ID = 'ram';
    
    /**
     * PPU寄存器设备的ID
     */
    private const PPU_REGISTERS_ID = 'ppu-registers';
    
    /**
     * 是否启用RAM镜像
     *
     * @var bool
     */
    private bool $ramMirroringEnabled = false;
    
    /**
     * 是否启用PPU寄存器镜像
     *
     * @var bool
     */
    private bool $ppuRegisterMirroringEnabled = false;
    
    /**
     * NMI中断处理器
     *
     * @var callable|null
     */
    private $nmiHandler = null;
    
    /**
     * IRQ中断处理器
     *
     * @var callable|null
     */
    private $irqHandler = null;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 设置默认的中断处理器
        $this->setInterruptHandler(InterruptSource::INTERRUPT_NMI, function () {
            if ($this->nmiHandler) {
                call_user_func($this->nmiHandler);
            }
        });
        
        $this->setInterruptHandler(InterruptSource::INTERRUPT_IRQ, function () {
            if ($this->irqHandler) {
                call_user_func($this->irqHandler);
            }
        });
    }
    
    /**
     * 启用RAM镜像
     *
     * NES的内部RAM是2KB，但它在$0000-$1FFF地址范围内被镜像了4次
     *
     * @return void
     */
    public function enableRamMirroring(): void
    {
        $this->ramMirroringEnabled = true;
    }
    
    /**
     * 禁用RAM镜像
     *
     * @return void
     */
    public function disableRamMirroring(): void
    {
        $this->ramMirroringEnabled = false;
    }
    
    /**
     * 启用PPU寄存器镜像
     *
     * PPU寄存器在$2000-$2007，但它在$2008-$3FFF被镜像了多次
     *
     * @return void
     */
    public function enablePpuRegisterMirroring(): void
    {
        $this->ppuRegisterMirroringEnabled = true;
    }
    
    /**
     * 禁用PPU寄存器镜像
     *
     * @return void
     */
    public function disablePpuRegisterMirroring(): void
    {
        $this->ppuRegisterMirroringEnabled = false;
    }
    
    /**
     * 设置NMI中断处理器
     *
     * @param callable $handler NMI处理函数
     * @return void
     */
    public function setNmiHandler(callable $handler): void
    {
        $this->nmiHandler = $handler;
    }
    
    /**
     * 设置IRQ中断处理器
     *
     * @param callable $handler IRQ处理函数
     * @return void
     */
    public function setIrqHandler(callable $handler): void
    {
        $this->irqHandler = $handler;
    }
    
    /**
     * 触发NMI中断
     *
     * @return void
     */
    public function triggerNmi(): void
    {
        $this->handleInterrupt(InterruptSource::INTERRUPT_NMI);
    }
    
    /**
     * 触发IRQ中断
     *
     * @return void
     */
    public function triggerIrq(): void
    {
        $this->handleInterrupt(InterruptSource::INTERRUPT_IRQ);
    }
    
    /**
     * {@inheritdoc}
     */
    public function read(int $address): int
    {
        // 如果启用了RAM镜像，处理RAM镜像区域
        if ($this->ramMirroringEnabled && $address >= 0x0000 && $address <= 0x1FFF) {
            return $this->handleRamMirroringRead($address);
        }
        
        // 如果启用了PPU寄存器镜像，处理PPU寄存器镜像区域
        if ($this->ppuRegisterMirroringEnabled && $address >= 0x2008 && $address <= 0x3FFF) {
            return $this->handlePpuRegisterMirroringRead($address);
        }
        
        // 调用父类的标准读取方法
        return parent::read($address);
    }
    
    /**
     * {@inheritdoc}
     */
    public function write(int $address, int $value): void
    {
        // 如果启用了RAM镜像，处理RAM镜像区域
        if ($this->ramMirroringEnabled && $address >= 0x0000 && $address <= 0x1FFF) {
            $this->handleRamMirroringWrite($address, $value);
            return;
        }
        
        // 如果启用了PPU寄存器镜像，处理PPU寄存器镜像区域
        if ($this->ppuRegisterMirroringEnabled && $address >= 0x2008 && $address <= 0x3FFF) {
            $this->handlePpuRegisterMirroringWrite($address, $value);
            return;
        }
        
        // 调用父类的标准写入方法
        parent::write($address, $value);
    }
    
    /**
     * 处理RAM镜像区域的读取
     *
     * @param int $address 原始地址
     * @return int 读取的数据
     * @throws MemoryAccessException 如果RAM设备未连接或地址无效
     */
    protected function handleRamMirroringRead(int $address): int
    {
        // 计算实际的RAM地址（对应到0x0000-0x07FF范围）
        $ramAddress = $address & 0x07FF;
        
        // 查找RAM设备
        $ramDevice = $this->findDeviceById(self::RAM_DEVICE_ID);
        
        if ($ramDevice instanceof AddressableDevice) {
            return $ramDevice->read($ramAddress);
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理RAM镜像区域的写入
     *
     * @param int $address 原始地址
     * @param int $value 要写入的值
     * @throws MemoryAccessException 如果RAM设备未连接或地址无效
     */
    protected function handleRamMirroringWrite(int $address, int $value): void
    {
        // 计算实际的RAM地址（对应到0x0000-0x07FF范围）
        $ramAddress = $address & 0x07FF;
        
        // 查找RAM设备
        $ramDevice = $this->findDeviceById(self::RAM_DEVICE_ID);
        
        if ($ramDevice instanceof AddressableDevice) {
            $ramDevice->write($ramAddress, $value);
            return;
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理PPU寄存器镜像区域的读取
     *
     * @param int $address 原始地址
     * @return int 读取的数据
     * @throws MemoryAccessException 如果PPU寄存器设备未连接或地址无效
     */
    protected function handlePpuRegisterMirroringRead(int $address): int
    {
        // 计算实际的PPU寄存器地址（对应到0x2000-0x2007范围）
        $ppuRegisterAddress = 0x2000 + ($address & 0x0007);
        
        // 查找PPU寄存器设备
        $ppuRegistersDevice = $this->findDeviceById(self::PPU_REGISTERS_ID);
        
        if ($ppuRegistersDevice instanceof AddressableDevice) {
            return $ppuRegistersDevice->read($ppuRegisterAddress);
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理PPU寄存器镜像区域的写入
     *
     * @param int $address 原始地址
     * @param int $value 要写入的值
     * @throws MemoryAccessException 如果PPU寄存器设备未连接或地址无效
     */
    protected function handlePpuRegisterMirroringWrite(int $address, int $value): void
    {
        // 计算实际的PPU寄存器地址（对应到0x2000-0x2007范围）
        $ppuRegisterAddress = 0x2000 + ($address & 0x0007);
        
        // 查找PPU寄存器设备
        $ppuRegistersDevice = $this->findDeviceById(self::PPU_REGISTERS_ID);
        
        if ($ppuRegistersDevice instanceof AddressableDevice) {
            $ppuRegistersDevice->write($ppuRegisterAddress, $value);
            return;
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 通过ID查找设备
     *
     * @param string $deviceId 设备ID
     * @return DeviceInterface|null 找到的设备，如果未找到则返回null
     */
    protected function findDeviceById(string $deviceId): ?DeviceInterface
    {
        if (isset($this->devices[$deviceId])) {
            return $this->devices[$deviceId]['device'];
        }
        
        return null;
    }
} 