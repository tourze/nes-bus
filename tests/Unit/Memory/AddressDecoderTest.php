<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Memory\AddressDecoder;

class AddressDecoderTest extends TestCase
{
    /**
     * 测试基本的地址映射规则创建
     */
    public function test_create_mapping_rule(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加一个映射规则：0x8000-0xFFFF 映射到 0x0000-0x7FFF
        $decoder->addMappingRule(
            0x8000,         // 源起始地址
            0xFFFF,         // 源结束地址
            0x0000,         // 目标起始地址
            function (int $sourceAddress): int {
                // 减去偏移量获取映射后的地址
                return 0x0000 + ($sourceAddress - 0x8000);
            }
        );
        
        // 验证映射功能
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x8000));
        $this->assertEquals(0x1234, $decoder->decodeAddress(0x9234));
        $this->assertEquals(0x7FFF, $decoder->decodeAddress(0xFFFF));
    }
    
    /**
     * 测试默认映射（不需要映射的地址）
     */
    public function test_default_mapping(): void
    {
        $decoder = new AddressDecoder();
        
        // 未添加映射规则的地址应该保持不变
        $this->assertEquals(0x1234, $decoder->decodeAddress(0x1234));
        $this->assertEquals(0xABCD, $decoder->decodeAddress(0xABCD));
    }
    
    /**
     * 测试优先级处理
     */
    public function test_mapping_priority(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加低优先级规则
        $decoder->addMappingRule(
            0x0000,
            0xFFFF,
            0x0000,
            function (int $sourceAddress): int {
                return $sourceAddress | 0x10000; // 设置一个高位标记
            },
            0
        );
        
        // 添加高优先级规则（特定范围）
        $decoder->addMappingRule(
            0x8000,
            0x9FFF,
            0x0000,
            function (int $sourceAddress): int {
                return 0x0000 + ($sourceAddress - 0x8000); // 映射到低地址
            },
            1
        );
        
        // 0x8000-0x9FFF 应该使用高优先级规则
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x8000));
        $this->assertEquals(0x1000, $decoder->decodeAddress(0x9000));
        
        // 其他地址应该使用低优先级规则
        $this->assertEquals(0x10000 | 0x7000, $decoder->decodeAddress(0x7000));
        $this->assertEquals(0x10000 | 0xA000, $decoder->decodeAddress(0xA000));
    }
    
    /**
     * 测试镜像映射
     */
    public function test_mirror_mapping(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加镜像映射规则：0x0000-0x1FFF 每2KB镜像
        $decoder->addMirrorRule(0x0000, 0x1FFF, 0x0800); // 2KB = 0x0800
        
        // 验证镜像
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x0000)); // 原始区域
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x0800)); // 第一个镜像
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x1000)); // 第二个镜像
        $this->assertEquals(0x0000, $decoder->decodeAddress(0x1800)); // 第三个镜像
        
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x0123)); // 原始区域
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x0923)); // 第一个镜像 (0x0800 + 0x0123)
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x1123)); // 第二个镜像 (0x1000 + 0x0123)
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x1923)); // 第三个镜像 (0x1800 + 0x0123)
    }
    
    /**
     * 测试复杂的多层映射
     */
    public function test_complex_mapping(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加RAM镜像规则
        $decoder->addMirrorRule(0x0000, 0x1FFF, 0x0800);
        
        // 添加PRG ROM镜像规则（16KB PRG镜像）
        $decoder->addMirrorRule(0x8000, 0xFFFF, 0x4000);
        
        // 添加PPU寄存器镜像
        $decoder->addMirrorRule(0x2000, 0x3FFF, 0x0008);
        
        // 验证RAM镜像
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x0923)); // RAM镜像
        
        // 验证PRG ROM镜像
        $this->assertEquals(0x8123, $decoder->decodeAddress(0xC123)); // PRG ROM镜像
        
        // 验证PPU寄存器镜像
        $this->assertEquals(0x2003, $decoder->decodeAddress(0x200B)); // PPU寄存器镜像
        $this->assertEquals(0x2003, $decoder->decodeAddress(0x2013)); // PPU寄存器镜像
    }
    
    /**
     * 测试映射规则的移除
     */
    public function test_remove_mapping_rule(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加一个规则
        $ruleId = $decoder->addMappingRule(
            0x8000,
            0xFFFF,
            0x0000,
            function (int $sourceAddress): int {
                return 0x0000 + ($sourceAddress - 0x8000);
            }
        );
        
        // 验证规则生效
        $this->assertEquals(0x1234, $decoder->decodeAddress(0x9234));
        
        // 移除规则
        $decoder->removeRule($ruleId);
        
        // 验证规则已移除
        $this->assertEquals(0x9234, $decoder->decodeAddress(0x9234));
    }
    
    /**
     * 测试清除所有规则
     */
    public function test_clear_all_rules(): void
    {
        $decoder = new AddressDecoder();
        
        // 添加多个规则
        $decoder->addMirrorRule(0x0000, 0x1FFF, 0x0800);
        $decoder->addMirrorRule(0x8000, 0xFFFF, 0x4000);
        
        // 验证规则生效
        $this->assertEquals(0x0123, $decoder->decodeAddress(0x0923));
        $this->assertEquals(0x8123, $decoder->decodeAddress(0xC123));
        
        // 清除所有规则
        $decoder->clearRules();
        
        // 验证所有规则已移除
        $this->assertEquals(0x0923, $decoder->decodeAddress(0x0923));
        $this->assertEquals(0xC123, $decoder->decodeAddress(0xC123));
    }
}
