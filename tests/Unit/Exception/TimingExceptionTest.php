<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Exception\BusException;
use Tourze\NES\Bus\Exception\TimingException;

class TimingExceptionTest extends TestCase
{
    /**
     * 测试异常是否正确继承自BusException
     */
    public function test_exception_extends_bus_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(TimingException::class, BusException::class),
            'TimingException应该继承自BusException'
        );
    }

    /**
     * 测试异常是否可以正确构造并携带错误信息
     */
    public function test_exception_constructor_and_message(): void
    {
        $message = 'Test timing error message';
        $exception = new TimingException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }
} 