<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Bus;

use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Device\DeviceInterface;
use Tourze\NES\Bus\Exception\MemoryAccessException;

/**
 * PPU总线实现
 * 
 * 实现NES PPU总线的特定功能，包括模式表访问和名称表镜像
 */
class PpuBus extends AbstractBus
{
    /**
     * 名称表设备的ID
     */
    private const NAMETABLE_DEVICE_ID = 'nametable';
    
    /**
     * 调色板设备的ID
     */
    private const PALETTE_DEVICE_ID = 'palette';
    
    /**
     * 模式表设备的ID
     */
    private const PATTERN_TABLE_DEVICE_ID = 'pattern-table';
    
    /**
     * 镜像模式
     */
    public const MIRROR_HORIZONTAL = 0;
    public const MIRROR_VERTICAL = 1;
    public const MIRROR_SINGLE_SCREEN_0 = 2;
    public const MIRROR_SINGLE_SCREEN_1 = 3;
    public const MIRROR_FOUR_SCREEN = 4;
    
    /**
     * 当前名称表镜像模式
     *
     * @var int
     */
    private int $mirrorMode = self::MIRROR_HORIZONTAL;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        // PPU总线不处理中断
    }
    
    /**
     * 设置名称表镜像模式
     *
     * @param int $mode 镜像模式
     * @return void
     */
    public function setMirrorMode(int $mode): void
    {
        $this->mirrorMode = $mode;
    }
    
    /**
     * 获取当前镜像模式
     *
     * @return int
     */
    public function getMirrorMode(): int
    {
        return $this->mirrorMode;
    }
    
    /**
     * {@inheritdoc}
     */
    public function read(int $address): int
    {
        // 地址范围检查 (PPU总线范围是0x0000-0x3FFF)
        if ($address < 0x0000 || $address > 0x3FFF) {
            throw MemoryAccessException::invalidAddress($address);
        }
        
        // 处理名称表镜像 (0x2000-0x2FFF)
        if ($address >= 0x2000 && $address < 0x3000) {
            return $this->handleNametableMirroringRead($address);
        }
        
        // 处理调色板镜像 (0x3F00-0x3FFF)
        if ($address >= 0x3F00 && $address <= 0x3FFF) {
            return $this->handlePaletteMirroringRead($address);
        }
        
        // 其他地址使用标准读取
        return parent::read($address);
    }
    
    /**
     * {@inheritdoc}
     */
    public function write(int $address, int $value): void
    {
        // 地址范围检查 (PPU总线范围是0x0000-0x3FFF)
        if ($address < 0x0000 || $address > 0x3FFF) {
            throw MemoryAccessException::invalidAddress($address);
        }
        
        // 处理名称表镜像 (0x2000-0x2FFF)
        if ($address >= 0x2000 && $address < 0x3000) {
            $this->handleNametableMirroringWrite($address, $value);
            return;
        }
        
        // 处理调色板镜像 (0x3F00-0x3FFF)
        if ($address >= 0x3F00 && $address <= 0x3FFF) {
            $this->handlePaletteMirroringWrite($address, $value);
            return;
        }
        
        // 其他地址使用标准写入
        parent::write($address, $value);
    }
    
    /**
     * 处理名称表镜像读取
     *
     * @param int $address 原始地址
     * @return int 读取的数据
     * @throws MemoryAccessException 如果名称表设备未连接或地址无效
     */
    protected function handleNametableMirroringRead(int $address): int
    {
        $nametableAddress = $this->mapNametableAddress($address);
        
        // 查找名称表设备
        $nametableDevice = $this->findDeviceById(self::NAMETABLE_DEVICE_ID);
        
        if ($nametableDevice instanceof AddressableDevice) {
            return $nametableDevice->read($nametableAddress);
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理名称表镜像写入
     *
     * @param int $address 原始地址
     * @param int $value 要写入的值
     * @throws MemoryAccessException 如果名称表设备未连接或地址无效
     */
    protected function handleNametableMirroringWrite(int $address, int $value): void
    {
        $nametableAddress = $this->mapNametableAddress($address);
        
        // 查找名称表设备
        $nametableDevice = $this->findDeviceById(self::NAMETABLE_DEVICE_ID);
        
        if ($nametableDevice instanceof AddressableDevice) {
            $nametableDevice->write($nametableAddress, $value);
            return;
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理调色板镜像读取
     *
     * @param int $address 原始地址
     * @return int 读取的数据
     * @throws MemoryAccessException 如果调色板设备未连接或地址无效
     */
    protected function handlePaletteMirroringRead(int $address): int
    {
        $paletteAddress = $this->mapPaletteAddress($address);
        
        // 查找调色板设备
        $paletteDevice = $this->findDeviceById(self::PALETTE_DEVICE_ID);
        
        if ($paletteDevice instanceof AddressableDevice) {
            return $paletteDevice->read($paletteAddress);
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 处理调色板镜像写入
     *
     * @param int $address 原始地址
     * @param int $value 要写入的值
     * @throws MemoryAccessException 如果调色板设备未连接或地址无效
     */
    protected function handlePaletteMirroringWrite(int $address, int $value): void
    {
        $paletteAddress = $this->mapPaletteAddress($address);
        
        // 查找调色板设备
        $paletteDevice = $this->findDeviceById(self::PALETTE_DEVICE_ID);
        
        if ($paletteDevice instanceof AddressableDevice) {
            $paletteDevice->write($paletteAddress, $value);
            return;
        }
        
        throw MemoryAccessException::invalidAddress($address);
    }
    
    /**
     * 根据镜像模式映射名称表地址
     *
     * @param int $address 原始地址 (0x2000-0x2FFF)
     * @return int 映射后的地址 (0x2000-0x23FF 范围内)
     */
    protected function mapNametableAddress(int $address): int
    {
        // 名称表地址相对于0x2000的偏移量
        $offset = $address & 0x0FFF;
        
        // 确定所请求的名称表 (0-3)
        $nametableId = ($offset >> 10) & 0x03;
        
        // 计算在名称表内的偏移量 (0-1023)
        $nametableOffset = $offset & 0x03FF;
        
        // 根据镜像模式映射名称表ID
        $mappedNametableId = match ($this->mirrorMode) {
            self::MIRROR_HORIZONTAL => $nametableId & 0x01 ? 1 : 0,
            self::MIRROR_VERTICAL => $nametableId & 0x02 ? 1 : 0,
            self::MIRROR_SINGLE_SCREEN_0 => 0,
            self::MIRROR_SINGLE_SCREEN_1 => 1,
            self::MIRROR_FOUR_SCREEN => $nametableId, // 四屏模式无镜像
            default => $nametableId & 0x01 ? 1 : 0, // 默认为水平镜像
        };
        
        // 计算最终地址: 0x2000 + (映射后的名称表ID * 1024) + 名称表内偏移量
        return 0x2000 + ($mappedNametableId * 0x0400) + $nametableOffset;
    }
    
    /**
     * 映射调色板地址
     *
     * @param int $address 原始地址 (0x3F00-0x3FFF)
     * @return int 映射后的地址 (0x3F00-0x3F1F 范围内)
     */
    protected function mapPaletteAddress(int $address): int
    {
        // 获取调色板地址 (0x00-0xFF)
        $paletteOffset = $address & 0x001F;
        
        // 调色板镜像: 0x10与0x00共用相同的值
        if ($paletteOffset == 0x10) {
            $paletteOffset = 0x00;
        }
        
        // 其他调色板镜像规则: 0x14=>0x04, 0x18=>0x08, 0x1C=>0x0C
        if (($paletteOffset & 0x13) == 0x10) {
            $paletteOffset &= 0x0F;
        }
        
        return 0x3F00 + $paletteOffset;
    }
    
    /**
     * 查找具有指定ID的设备
     *
     * @param string $deviceId 设备ID
     * @return DeviceInterface|null 找到的设备，如果没有找到则返回null
     */
    protected function findDeviceById(string $deviceId): ?DeviceInterface
    {
        foreach ($this->devices as $id => $deviceInfo) {
            if ($id === $deviceId) {
                return $deviceInfo['device'];
            }
        }
        
        return null;
    }
} 