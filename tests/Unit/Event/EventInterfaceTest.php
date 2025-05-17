<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Event\EventInterface;

class EventInterfaceTest extends TestCase
{
    /**
     * 测试接口是否定义了所需的方法
     */
    public function test_interface_defines_required_methods(): void
    {
        // 使用反射API检查接口
        $reflection = new \ReflectionClass(EventInterface::class);
        
        // 检查接口是否定义了getName方法
        $this->assertTrue(
            $reflection->hasMethod('getName'), 
            'EventInterface应该定义getName方法'
        );
        
        // 检查getName方法签名
        $getNameMethod = $reflection->getMethod('getName');
        $this->assertTrue(
            $getNameMethod->getReturnType()->getName() === 'string',
            'getName方法应该返回string类型'
        );
        
        // 检查接口是否定义了getPayload方法
        $this->assertTrue(
            $reflection->hasMethod('getPayload'), 
            'EventInterface应该定义getPayload方法'
        );
        
        // 检查getPayload方法签名
        $getPayloadMethod = $reflection->getMethod('getPayload');
        $this->assertTrue(
            $getPayloadMethod->getReturnType()->getName() === 'array',
            'getPayload方法应该返回array类型'
        );
        
        // 检查接口是否定义了getTimestamp方法
        $this->assertTrue(
            $reflection->hasMethod('getTimestamp'), 
            'EventInterface应该定义getTimestamp方法'
        );
        
        // 检查getTimestamp方法签名
        $getTimestampMethod = $reflection->getMethod('getTimestamp');
        $this->assertTrue(
            $getTimestampMethod->getReturnType()->getName() === 'float',
            'getTimestamp方法应该返回float类型'
        );
    }
}
