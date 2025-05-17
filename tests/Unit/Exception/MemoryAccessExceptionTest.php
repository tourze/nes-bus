<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Exception\BusException;
use Tourze\NES\Bus\Exception\MemoryAccessException;

class MemoryAccessExceptionTest extends TestCase
{
    /**
     * 测试异常是否正确继承自BusException
     */
    public function test_exception_extends_bus_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(MemoryAccessException::class, BusException::class),
            'MemoryAccessException应该继承自BusException'
        );
    }

    /**
     * 测试异常是否可以正确构造并携带错误信息
     */
    public function test_exception_constructor_and_message(): void
    {
        $message = 'Test memory access error message';
        $exception = new MemoryAccessException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * 测试带地址的构造函数
     */
    public function test_exception_with_address(): void
    {
        $address = 0x8000;
        $exception = MemoryAccessException::invalidAddress($address);
        
        $this->assertStringContainsString(
            dechex($address),
            $exception->getMessage(),
            '异常消息应该包含地址的十六进制表示'
        );
    }
} 