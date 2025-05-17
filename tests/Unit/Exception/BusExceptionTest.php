<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Exception\BusException;

class BusExceptionTest extends TestCase
{
    /**
     * 测试异常是否正确继承自Exception
     */
    public function test_exception_extends_base_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(BusException::class, \Exception::class),
            'BusException应该继承自Exception'
        );
    }

    /**
     * 测试异常是否可以正确构造并携带错误信息
     */
    public function test_exception_constructor_and_message(): void
    {
        $message = 'Test bus error message';
        $exception = new BusException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }
} 