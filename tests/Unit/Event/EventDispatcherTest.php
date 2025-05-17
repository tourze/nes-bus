<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Tourze\NES\Bus\Event\BusEvent;
use Tourze\NES\Bus\Event\EventDispatcher;
use Tourze\NES\Bus\Event\EventInterface;

class EventDispatcherTest extends TestCase
{
    /**
     * 测试添加事件监听器
     */
    public function test_add_event_listener(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 添加一个事件监听器
        $result = $dispatcher->addEventListener('test.event', function(EventInterface $event) {
            // 不做任何事情
        });
        
        $this->assertTrue($result);
        $this->assertTrue($dispatcher->hasEventListener('test.event'));
    }
    
    /**
     * 测试移除事件监听器
     */
    public function test_remove_event_listener(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 创建一个命名的监听器函数
        $listener = function(EventInterface $event) {
            // 不做任何事情
        };
        
        // 添加然后移除监听器
        $dispatcher->addEventListener('test.event', $listener);
        $result = $dispatcher->removeEventListener('test.event', $listener);
        
        $this->assertTrue($result);
        $this->assertFalse($dispatcher->hasEventListener('test.event'));
    }
    
    /**
     * 测试事件分发
     */
    public function test_dispatch_event(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new BusEvent('test.event', ['data' => 'value']);
        
        // 设置期望值
        $listenerCalled = false;
        $receivedEvent = null;
        
        // 添加监听器
        $dispatcher->addEventListener('test.event', function(EventInterface $e) use (&$listenerCalled, &$receivedEvent) {
            $listenerCalled = true;
            $receivedEvent = $e;
        });
        
        // 分发事件
        $dispatcher->dispatchEvent($event);
        
        // 验证监听器被调用
        $this->assertTrue($listenerCalled);
        $this->assertSame($event, $receivedEvent);
    }
    
    /**
     * 测试事件分发给多个监听器
     */
    public function test_dispatch_to_multiple_listeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new BusEvent('test.event', ['data' => 'value']);
        
        // 跟踪调用次数
        $callCount = 0;
        
        // 添加多个监听器
        $dispatcher->addEventListener('test.event', function(EventInterface $e) use (&$callCount) {
            $callCount++;
        });
        
        $dispatcher->addEventListener('test.event', function(EventInterface $e) use (&$callCount) {
            $callCount++;
        });
        
        $dispatcher->addEventListener('other.event', function(EventInterface $e) use (&$callCount) {
            $callCount++;
        });
        
        // 分发事件
        $dispatcher->dispatchEvent($event);
        
        // 验证只有匹配事件名称的监听器被调用
        $this->assertEquals(2, $callCount);
    }
    
    /**
     * 测试通配符事件监听器
     */
    public function test_wildcard_event_listener(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 跟踪调用次数
        $allEventsCount = 0;
        $busEventsCount = 0;
        
        // 添加通配符监听器
        $dispatcher->addEventListener('*', function(EventInterface $e) use (&$allEventsCount) {
            $allEventsCount++;
        });
        
        $dispatcher->addEventListener('bus.*', function(EventInterface $e) use (&$busEventsCount) {
            $busEventsCount++;
        });
        
        // 分发不同类型的事件
        $dispatcher->dispatchEvent(new BusEvent('bus.read'));
        $dispatcher->dispatchEvent(new BusEvent('bus.write'));
        $dispatcher->dispatchEvent(new BusEvent('device.attached'));
        
        // 验证监听器调用次数
        $this->assertEquals(3, $allEventsCount); // 所有事件
        $this->assertEquals(2, $busEventsCount); // 只有bus.*事件
    }
    
    /**
     * 测试一次性事件监听器
     */
    public function test_one_time_event_listener(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 跟踪调用次数
        $callCount = 0;
        
        // 添加一次性监听器
        $dispatcher->addOneTimeEventListener('test.event', function(EventInterface $e) use (&$callCount) {
            $callCount++;
        });
        
        // 分发事件两次
        $dispatcher->dispatchEvent(new BusEvent('test.event'));
        $dispatcher->dispatchEvent(new BusEvent('test.event'));
        
        // 验证监听器只被调用一次
        $this->assertEquals(1, $callCount);
        $this->assertFalse($dispatcher->hasEventListener('test.event'));
    }
    
    /**
     * 测试清除所有监听器
     */
    public function test_clear_all_listeners(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 添加多个监听器
        $dispatcher->addEventListener('test.event1', function(EventInterface $e) {});
        $dispatcher->addEventListener('test.event2', function(EventInterface $e) {});
        
        // 清除所有监听器
        $dispatcher->clearEventListeners();
        
        // 验证所有监听器已移除
        $this->assertFalse($dispatcher->hasEventListener('test.event1'));
        $this->assertFalse($dispatcher->hasEventListener('test.event2'));
    }
    
    /**
     * 测试清除特定事件的所有监听器
     */
    public function test_clear_listeners_for_event(): void
    {
        $dispatcher = new EventDispatcher();
        
        // 添加多个监听器
        $dispatcher->addEventListener('test.event1', function(EventInterface $e) {});
        $dispatcher->addEventListener('test.event1', function(EventInterface $e) {});
        $dispatcher->addEventListener('test.event2', function(EventInterface $e) {});
        
        // 清除特定事件的监听器
        $dispatcher->clearEventListenersForEvent('test.event1');
        
        // 验证监听器状态
        $this->assertFalse($dispatcher->hasEventListener('test.event1'));
        $this->assertTrue($dispatcher->hasEventListener('test.event2'));
    }
} 