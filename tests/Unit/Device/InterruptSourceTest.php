<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Device;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Device\InterruptSource;

class InterruptSourceTest extends TestCase
{
    /**
     * 测试接口是否定义了所需的方法
     */
    public function test_interface_defines_required_methods(): void
    {
        // 使用反射API检查接口
        $reflection = new \ReflectionClass(InterruptSource::class);
        
        // 检查接口是否定义了hasInterrupt方法
        $this->assertTrue(
            $reflection->hasMethod('hasInterrupt'), 
            'InterruptSource应该定义hasInterrupt方法'
        );
        
        // 检查hasInterrupt方法签名
        $hasInterruptMethod = $reflection->getMethod('hasInterrupt');
        $this->assertTrue(
            $hasInterruptMethod->getReturnType()->getName() === 'bool',
            'hasInterrupt方法应该返回bool类型'
        );
        
        // 检查接口是否定义了getInterruptType方法
        $this->assertTrue(
            $reflection->hasMethod('getInterruptType'), 
            'InterruptSource应该定义getInterruptType方法'
        );
        
        // 检查getInterruptType方法签名
        $getInterruptTypeMethod = $reflection->getMethod('getInterruptType');
        $this->assertTrue(
            $getInterruptTypeMethod->getReturnType()->getName() === 'int',
            'getInterruptType方法应该返回int类型'
        );
        
        // 检查接口是否定义了clearInterrupt方法
        $this->assertTrue(
            $reflection->hasMethod('clearInterrupt'), 
            'InterruptSource应该定义clearInterrupt方法'
        );
        
        // 检查clearInterrupt方法签名
        $clearInterruptMethod = $reflection->getMethod('clearInterrupt');
        $this->assertTrue(
            $clearInterruptMethod->getReturnType()->getName() === 'void',
            'clearInterrupt方法应该返回void类型'
        );
    }
} 