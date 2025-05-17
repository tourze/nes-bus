<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Device;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Device\DeviceInterface;

class DeviceInterfaceTest extends TestCase
{
    /**
     * 测试接口是否定义了所需的方法
     */
    public function test_interface_defines_required_methods(): void
    {
        // 使用反射API检查接口
        $reflection = new \ReflectionClass(DeviceInterface::class);

        // 检查接口是否定义了getBusId方法
        $this->assertTrue(
            $reflection->hasMethod('getBusId'),
            'DeviceInterface应该定义getBusId方法'
        );

        // 检查getBusId方法签名
        $getBusIdMethod = $reflection->getMethod('getBusId');
        $this->assertTrue(
            $getBusIdMethod->getReturnType()->getName() === 'string',
            'getBusId方法应该返回string类型'
        );

        // 检查接口是否定义了reset方法
        $this->assertTrue(
            $reflection->hasMethod('reset'),
            'DeviceInterface应该定义reset方法'
        );

        // 检查reset方法签名
        $resetMethod = $reflection->getMethod('reset');
        $this->assertTrue(
            $resetMethod->getReturnType()->getName() === 'void',
            'reset方法应该返回void类型'
        );
    }
}
