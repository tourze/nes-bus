# NES总线系统开发计划

## 1. 概述

NES总线系统(Bus System)是NES模拟器的核心组件，负责连接CPU、PPU、APU和内存等各个硬件组件，主要功能包括：
- 管理组件间的通信
- 实现内存映射和地址解码
- 处理硬件中断的传递
- 精确模拟硬件时序和内存读写行为

NES系统中有两条主要总线：
- CPU总线(CPU Bus): 地址范围0x0000-0xFFFF (16位地址线)
- PPU总线(PPU Bus): 地址范围0x0000-0x3FFF (14位地址线)

本模块将与CPU、PPU、APU、卡带和内存等模块交互，为它们提供统一的通信接口。

## 2. 目录结构

```shell
nes-bus/
├── src/
│   ├── Bus/                      # 总线实现
│   │   ├── BusInterface.php      # 总线通用接口 ✅
│   │   ├── AbstractBus.php       # 总线抽象基类 ✅
│   │   ├── CpuBus.php            # CPU总线实现 ✅
│   │   └── PpuBus.php            # PPU总线实现 ✅
│   ├── Memory/                   # 内存管理
│   │   ├── MemoryInterface.php   # 内存接口 ✅
│   │   ├── MemoryMap.php         # 内存映射管理 ✅
│   │   ├── Ram.php               # RAM实现 ✅
│   │   └── AddressDecoder.php    # 地址解码器 ✅
│   ├── Device/                   # 设备接口
│   │   ├── DeviceInterface.php   # 设备通用接口 ✅
│   │   ├── InterruptSource.php   # 中断源接口 ✅
│   │   └── AddressableDevice.php # 可寻址设备接口 ✅
│   ├── Exception/                # 异常处理
│   │   ├── BusException.php      # 总线异常基类 ✅
│   │   ├── MemoryAccessException.php # 内存访问异常 ✅
│   │   └── TimingException.php   # 时序异常 ✅
│   ├── Event/                    # 事件系统
│   │   ├── EventInterface.php    # 事件接口 ✅
│   │   ├── EventDispatcher.php   # 事件分发器 ✅
│   │   └── BusEvent.php          # 总线事件定义 ✅
│   └── Timing/                   # 时序控制
│       ├── ClockInterface.php    # 时钟接口 ✅
│       ├── CycleCounter.php      # 周期计数器 ⬜
│       └── TimingController.php  # 时序控制器 ⬜
├── tests/                        # 测试目录
│   ├── Unit/                     # 单元测试
│   │   ├── Bus/                  # 总线测试 ✅
│   │   ├── Memory/               # 内存测试 ✅
│   │   └── Timing/               # 时序测试 ✅
│   └── Integration/              # 集成测试
│       └── BusIntegrationTest.php # 总线集成测试 ⬜
└── README.md                     # 文档 ✅
```

## 3. 模块分层设计

1. **接口层**
   - `BusInterface` - 定义总线的公共接口 ✅
   - `MemoryInterface` - 定义内存访问接口 ✅
   - `DeviceInterface` - 定义连接到总线的设备接口 ✅
   - `InterruptSource` - 定义中断源的接口 ✅
   - `ClockInterface` - 定义时钟信号接口 ✅

2. **抽象层**
   - `AbstractBus` - 提供基本总线功能和共享方法 ✅

3. **实现层**
   - `CpuBus` - CPU总线的具体实现 ✅
   - `PpuBus` - PPU总线的具体实现 ✅
   - `Ram` - RAM内存的实现 ✅
   - `MemoryMap` - 内存映射实现 ✅

4. **事件系统**
   - `EventDispatcher` - 处理总线上的事件通知 ✅
   - `BusEvent` - 总线事件类型定义 ✅

5. **时序控制**
   - `CycleCounter` - 处理CPU和PPU时钟周期 ⬜
   - `TimingController` - 控制组件间的时序关系 ⬜

## 4. 类级别设计

### 接口与抽象类

- **BusInterface**：定义总线的基本操作 ✅
  - 方法：`read(int $address): int` - 从总线读取数据
  - 方法：`write(int $address, int $value): void` - 向总线写入数据
  - 方法：`attachDevice(DeviceInterface $device, int $startAddress, int $endAddress): void` - 连接设备到总线
  - 方法：`detachDevice(DeviceInterface $device): void` - 从总线断开设备
  - 方法：`handleInterrupt(int $type): void` - 处理中断信号

- **MemoryInterface**：定义内存访问操作 ✅
  - 方法：`read(int $address): int` - 读取内存
  - 方法：`write(int $address, int $value): void` - 写入内存
  - 方法：`getSize(): int` - 获取内存大小

- **DeviceInterface**：定义连接到总线的设备 ✅
  - 方法：`getBusId(): string` - 获取设备在总线上的标识
  - 方法：`reset(): void` - 重置设备状态

- **AddressableDevice**：扩展DeviceInterface，表示可寻址设备 ✅
  - 方法：`read(int $address): int` - 设备读取操作
  - 方法：`write(int $address, int $value): void` - 设备写入操作
  - 方法：`getAddressRange(): array` - 获取设备的地址范围

- **InterruptSource**：定义可产生中断的设备 ✅
  - 方法：`hasInterrupt(): bool` - 检查是否有待处理的中断
  - 方法：`getInterruptType(): int` - 获取中断类型
  - 方法：`clearInterrupt(): void` - 清除中断标志

- **AbstractBus**：总线抽象基类 ✅
  - 属性：设备映射表、中断源列表、事件分发器
  - 功能：实现基本总线操作，提供读写和中断处理能力

### 具体实现类

- **CpuBus**：CPU总线实现 ✅
  - 支持地址范围：0x0000-0xFFFF
  - 处理CPU相关的内存映射
  - 管理NMI、IRQ、RESET中断

- **PpuBus**：PPU总线实现 ✅
  - 支持地址范围：0x0000-0x3FFF
  - 处理图形相关的内存映射

- **Ram**：内存实现 ✅
  - 支持基本的读写操作
  - 可配置内存大小和初始状态

- **MemoryMap**：内存映射管理 ✅
  - 处理地址映射和镜像
  - 管理只读/可写内存区域

- **AddressDecoder**：地址解码器 ✅
  - 根据地址确定对应的设备
  - 处理地址镜像和映射

### 事件系统

- **EventInterface**：定义事件接口 ✅
  - 方法：`getName(): string` - 获取事件名称
  - 方法：`getPayload(): array` - 获取事件数据

- **EventDispatcher**：事件分发器 ✅
  - 方法：`addEventListener(string $eventName, callable $listener): void` - 注册事件监听器
  - 方法：`removeEventListener(string $eventName, callable $listener): void` - 移除事件监听器
  - 方法：`dispatchEvent(EventInterface $event): void` - 分发事件

- **BusEvent**：总线事件类 ✅
  - 读写事件
  - 中断事件
  - 时钟事件

### 时序控制

- **ClockInterface**：时钟接口 ✅
  - 方法：`tick(): void` - 时钟周期推进
  - 方法：`getCycles(): int` - 获取当前周期数

- **CycleCounter**：周期计数器 ⬜
  - 跟踪CPU和PPU的时钟周期
  - 处理不同组件之间的时序关系

- **TimingController**：时序控制器 ⬜
  - 管理总线上的时序同步
  - 协调不同时钟域之间的关系

### 异常处理

- **BusException**：总线异常基类 ✅
  - 处理一般总线错误

- **MemoryAccessException**：内存访问异常 ✅
  - 处理非法内存访问、地址越界等问题

- **TimingException**：时序异常 ✅
  - 处理时序相关的错误

## 5. 完成进度规划

| 模块 | 类 | 状态 | 优先级 | 依赖项 |
|------|-----|------|--------|--------|
| 接口 | BusInterface | ✅ 已完成 | 最高 | 无 |
| 接口 | MemoryInterface | ✅ 已完成 | 高 | 无 |
| 接口 | DeviceInterface | ✅ 已完成 | 高 | 无 |
| 接口 | InterruptSource | ✅ 已完成 | 高 | 无 |
| 接口 | ClockInterface | ✅ 已完成 | 高 | 无 |
| 总线 | AbstractBus | ✅ 已完成 | 最高 | BusInterface |
| 总线 | CpuBus | ✅ 已完成 | 最高 | AbstractBus |
| 总线 | PpuBus | ✅ 已完成 | 高 | AbstractBus |
| 内存 | Ram | ✅ 已完成 | 高 | MemoryInterface |
| 内存 | MemoryMap | ✅ 已完成 | 高 | 无 |
| 内存 | AddressDecoder | ✅ 已完成 | 中 | 无 |
| 事件 | EventInterface | ✅ 已完成 | 中 | 无 |
| 事件 | EventDispatcher | ✅ 已完成 | 中 | EventInterface |
| 事件 | BusEvent | ✅ 已完成 | 中 | EventInterface |
| 时序 | CycleCounter | ⬜ 未开始 | 中 | ClockInterface |
| 时序 | TimingController | ⬜ 未开始 | 中 | ClockInterface |
| 异常 | BusException | ✅ 已完成 | 中 | 无 |
| 异常 | MemoryAccessException | ✅ 已完成 | 中 | BusException |
| 异常 | TimingException | ✅ 已完成 | 中 | BusException |

## 6. 实施步骤

1. **阶段1：基础接口与结构** ✅
   - 实现BusInterface接口
   - 实现MemoryInterface接口
   - 实现DeviceInterface接口
   - 实现异常处理类
   
2. **阶段2：核心总线实现** ✅
   - 实现AbstractBus抽象类
   - 实现基础的CpuBus
   - 实现基础的Ram实现

3. **阶段3：内存映射** ✅
   - 实现MemoryMap
   - 实现AddressDecoder
   - 完善CpuBus的内存映射支持

4. **阶段4：中断处理** ✅
   - 实现InterruptSource接口
   - 在CpuBus中实现中断处理逻辑

5. **阶段5：PPU总线** ✅
   - 实现PpuBus
   - 实现PPU相关的内存映射

6. **阶段6：时序控制** ⬜
   - 实现ClockInterface接口
   - 实现CycleCounter
   - 实现TimingController

7. **阶段7：事件系统** ✅
   - 实现EventInterface
   - 实现EventDispatcher
   - 实现BusEvent

8. **阶段8：完善和测试** ⬜
   - 实现完整的测试覆盖
   - 性能优化
   - 文档完善

## 7. 与其他模块的交互

总线模块(`nes-bus`)与其他NES模拟器模块的交互设计：

1. **与CPU模块的交互**：
   - CPU通过总线读写内存和IO设备
   - 总线向CPU传递中断信号(NMI、IRQ、RESET)
   - CPU将指令执行时序信息传递给总线

2. **与PPU模块的交互**：
   - PPU通过总线访问图形内存(Pattern Tables、Name Tables等)
   - PPU通过CPU总线暴露自己的寄存器(0x2000-0x2007)
   - PPU通过总线向CPU发送NMI中断

3. **与APU模块的交互**：
   - APU通过总线暴露自己的寄存器
   - APU可通过总线发送IRQ中断

4. **与卡带模块的交互**：
   - 总线访问卡带中的PRG ROM、CHR ROM和SRAM
   - 卡带中的映射器(Mapper)控制内存映射
   - 映射器可能会向总线发送IRQ中断

5. **与输入设备的交互**：
   - 控制器通过总线暴露输入状态寄存器

## 8. 优先级说明

总线功能的实现优先级：

1. **优先级最高**：
   - 基本的CPU总线读写功能
   - 简单的RAM实现
   - 基本的内存映射

2. **优先级高**：
   - CPU与PPU的通信
   - 简单的中断处理
   - 基本的PPU总线实现

3. **优先级中**：
   - 复杂的内存镜像和映射
   - 完整的中断处理
   - 时序控制

4. **优先级低**：
   - 事件系统
   - 更精确的时序模拟

## 9. 技术挑战与解决方案

1. **精确的时序模拟**：
   - 挑战：NES的CPU和PPU有不同的时钟频率，需要精确模拟它们之间的时序关系
   - 解决方案：实现CycleCounter和TimingController，管理不同组件的时钟周期

2. **内存映射复杂性**：
   - 挑战：NES的内存映射包含多种镜像和特殊映射区域
   - 解决方案：实现灵活的MemoryMap和AddressDecoder，处理不同的映射情况

3. **中断处理时序**：
   - 挑战：需要在正确的时序处理NMI、IRQ等中断
   - 解决方案：在总线层面实现中断优先级和队列机制

4. **性能与精确度平衡**：
   - 挑战：需要在模拟精确度和运行性能之间取得平衡
   - 解决方案：可配置的精确度级别，允许调整模拟细节
