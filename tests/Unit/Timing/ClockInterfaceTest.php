<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Timing;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Timing\ClockInterface;

class ClockInterfaceTest extends TestCase
{
    /**
     * 测试接口是否定义了所需的方法
     */
    public function test_interface_defines_required_methods(): void
    {
        // 使用反射API检查接口
        $reflection = new \ReflectionClass(ClockInterface::class);
        
        // 检查接口是否定义了tick方法
        $this->assertTrue(
            $reflection->hasMethod('tick'), 
            'ClockInterface应该定义tick方法'
        );
        
        // 检查tick方法签名
        $tickMethod = $reflection->getMethod('tick');
        $this->assertTrue(
            $tickMethod->getReturnType()->getName() === 'void',
            'tick方法应该返回void类型'
        );
        
        // 检查接口是否定义了getCycles方法
        $this->assertTrue(
            $reflection->hasMethod('getCycles'), 
            'ClockInterface应该定义getCycles方法'
        );
        
        // 检查getCycles方法签名
        $getCyclesMethod = $reflection->getMethod('getCycles');
        $this->assertTrue(
            $getCyclesMethod->getReturnType()->getName() === 'int',
            'getCycles方法应该返回int类型'
        );
    }
} 