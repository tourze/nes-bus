<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Device;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Device\AddressableDevice;
use Tourze\NES\Bus\Device\DeviceInterface;

class AddressableDeviceTest extends TestCase
{
    /**
     * 测试接口是否定义了所需的方法
     */
    public function test_interface_defines_required_methods(): void
    {
        // 使用反射API检查接口
        $reflection = new \ReflectionClass(AddressableDevice::class);

        // 检查接口是否继承自DeviceInterface
        $this->assertTrue(
            $reflection->implementsInterface(DeviceInterface::class),
            'AddressableDevice应该继承自DeviceInterface'
        );

        // 检查接口是否定义了read方法
        $this->assertTrue(
            $reflection->hasMethod('read'),
            'AddressableDevice应该定义read方法'
        );

        // 检查read方法签名
        $readMethod = $reflection->getMethod('read');
        $this->assertTrue(
            $readMethod->getReturnType()->getName() === 'int',
            'read方法应该返回int类型'
        );
        $readParams = $readMethod->getParameters();
        $this->assertCount(1, $readParams, 'read方法应该有一个参数');
        $this->assertEquals('address', $readParams[0]->getName(), 'read方法参数名应为address');
        $this->assertEquals('int', $readParams[0]->getType()->getName(), 'address参数应为int类型');

        // 检查接口是否定义了write方法
        $this->assertTrue(
            $reflection->hasMethod('write'),
            'AddressableDevice应该定义write方法'
        );

        // 检查write方法签名
        $writeMethod = $reflection->getMethod('write');
        $this->assertTrue(
            $writeMethod->getReturnType()->getName() === 'void',
            'write方法应该返回void类型'
        );
        $writeParams = $writeMethod->getParameters();
        $this->assertCount(2, $writeParams, 'write方法应该有两个参数');
        $this->assertEquals('address', $writeParams[0]->getName(), 'write方法第一个参数名应为address');
        $this->assertEquals('int', $writeParams[0]->getType()->getName(), 'address参数应为int类型');
        $this->assertEquals('value', $writeParams[1]->getName(), 'write方法第二个参数名应为value');
        $this->assertEquals('int', $writeParams[1]->getType()->getName(), 'value参数应为int类型');

        // 检查接口是否定义了getAddressRange方法
        $this->assertTrue(
            $reflection->hasMethod('getAddressRange'),
            'AddressableDevice应该定义getAddressRange方法'
        );

        // 检查getAddressRange方法签名
        $getAddressRangeMethod = $reflection->getMethod('getAddressRange');
        $this->assertTrue(
            $getAddressRangeMethod->getReturnType()->getName() === 'array',
            'getAddressRange方法应该返回array类型'
        );
    }
}
