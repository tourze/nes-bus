# NES 总线系统

NES 总线系统是 NES 模拟器的核心组件，负责连接 CPU、PPU、APU 和内存等各个硬件组件。

## 功能特点

- 实现完整的 CPU 总线和 PPU 总线
- 管理组件间的通信和数据传输
- 实现精确的内存映射和地址解码
- 处理硬件中断的传递
- 模拟准确的硬件时序

## 总线系统架构

NES 系统中存在两条主要总线：

1. **CPU 总线 (CPU Bus)**
   - 地址范围：0x0000-0xFFFF (16位地址线)
   - 连接：CPU、主内存(RAM)、PPU寄存器、APU寄存器、卡带(PRG ROM/RAM)、控制器

2. **PPU 总线 (PPU Bus)**
   - 地址范围：0x0000-0x3FFF (14位地址线)
   - 连接：PPU、图案表(Pattern Tables)、名称表(Name Tables)、调色板(Palette)、卡带(CHR ROM/RAM)

## 安装

通过 Composer 安装:

```bash
composer require tourze/nes-bus
```

## 基本用法

```php
// 创建总线实例
$cpuBus = new \Tourze\NES\Bus\Bus\CpuBus();
$ppuBus = new \Tourze\NES\Bus\Bus\PpuBus();

// 创建内存
$ram = new \Tourze\NES\Bus\Memory\Ram(0x0800); // 2KB RAM

// 连接设备到总线
$cpuBus->attachDevice($ram, 0x0000, 0x07FF);

// 读写操作
$cpuBus->write(0x0000, 0x42);
$value = $cpuBus->read(0x0000); // 返回 0x42
```

## 内存映射

### CPU 总线地址映射

| 地址范围     | 大小   | 描述                                     |
|--------------|--------|------------------------------------------|
| 0x0000-0x07FF | 2KB    | 内部 RAM                                |
| 0x0800-0x1FFF | 6KB    | 内部 RAM 镜像 (3次)                      |
| 0x2000-0x2007 | 8B     | PPU 寄存器                              |
| 0x2008-0x3FFF | 8KB-8B | PPU 寄存器镜像                          |
| 0x4000-0x4017 | 24B    | APU 和 IO 寄存器                        |
| 0x4018-0x401F | 8B     | APU 和 IO 功能测试模式                   |
| 0x4020-0xFFFF | ~48KB  | 卡带空间 (PRG ROM, PRG RAM 和映射器寄存器) |

### PPU 总线地址映射

| 地址范围     | 大小   | 描述                                     |
|--------------|--------|------------------------------------------|
| 0x0000-0x1FFF | 8KB    | 图案表 (Pattern Tables)，通常映射到卡带的 CHR ROM |
| 0x2000-0x2FFF | 4KB    | 名称表 (Name Tables)，存储在 VRAM 中     |
| 0x3000-0x3EFF | 4KB    | 名称表镜像                              |
| 0x3F00-0x3F1F | 32B    | 调色板 (Palette) RAM 索引               |
| 0x3F20-0x3FFF | 224B   | 调色板 RAM 镜像                         |

## 贡献

欢迎贡献代码、报告问题或提出改进建议！

## 授权

本项目采用 MIT 授权协议。
