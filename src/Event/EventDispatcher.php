<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Event;

/**
 * 事件分发器类
 * 
 * 管理事件监听器的注册和事件分发
 */
class EventDispatcher
{
    /**
     * 事件监听器列表
     * 
     * @var array<string, array<callable>>
     */
    private array $listeners = [];
    
    /**
     * 一次性事件监听器列表
     * 
     * @var array<string, array<callable>>
     */
    private array $oneTimeListeners = [];
    
    /**
     * 添加事件监听器
     *
     * @param string $eventName 事件名称，支持通配符（*）
     * @param callable $listener 监听器回调函数，参数为EventInterface
     * @return bool 添加是否成功
     */
    public function addEventListener(string $eventName, callable $listener): bool
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        
        $this->listeners[$eventName][] = $listener;
        return true;
    }
    
    /**
     * 添加一次性事件监听器
     *
     * @param string $eventName 事件名称，支持通配符（*）
     * @param callable $listener 监听器回调函数，参数为EventInterface
     * @return bool 添加是否成功
     */
    public function addOneTimeEventListener(string $eventName, callable $listener): bool
    {
        if (!isset($this->oneTimeListeners[$eventName])) {
            $this->oneTimeListeners[$eventName] = [];
        }
        
        $this->oneTimeListeners[$eventName][] = $listener;
        return true;
    }
    
    /**
     * 移除事件监听器
     *
     * @param string $eventName 事件名称
     * @param callable $listener 要移除的监听器回调函数
     * @return bool 移除是否成功
     */
    public function removeEventListener(string $eventName, callable $listener): bool
    {
        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        
        // 查找并移除匹配的监听器
        $key = array_search($listener, $this->listeners[$eventName], true);
        if ($key !== false) {
            unset($this->listeners[$eventName][$key]);
            
            // 如果没有更多监听器，移除整个事件名称键
            if (empty($this->listeners[$eventName])) {
                unset($this->listeners[$eventName]);
            } else {
                // 重建索引
                $this->listeners[$eventName] = array_values($this->listeners[$eventName]);
            }
            
            return true;
        }
        
        // 同样检查一次性监听器
        if (isset($this->oneTimeListeners[$eventName])) {
            $key = array_search($listener, $this->oneTimeListeners[$eventName], true);
            if ($key !== false) {
                unset($this->oneTimeListeners[$eventName][$key]);
                
                if (empty($this->oneTimeListeners[$eventName])) {
                    unset($this->oneTimeListeners[$eventName]);
                } else {
                    // 重建索引
                    $this->oneTimeListeners[$eventName] = array_values($this->oneTimeListeners[$eventName]);
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查是否有指定事件的监听器
     *
     * @param string $eventName 事件名称
     * @return bool 是否有监听器
     */
    public function hasEventListener(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) || isset($this->oneTimeListeners[$eventName]);
    }
    
    /**
     * 分发事件
     *
     * @param EventInterface $event 要分发的事件
     * @return void
     */
    public function dispatchEvent(EventInterface $event): void
    {
        $eventName = $event->getName();
        
        // 调用直接匹配的监听器
        $this->callListeners($eventName, $event);
        
        // 处理通配符匹配（*作为前缀或后缀）
        foreach ($this->listeners as $pattern => $patternListeners) {
            if ($pattern === '*' || $this->matchesWildcard($eventName, $pattern)) {
                foreach ($patternListeners as $listener) {
                    $listener($event);
                }
            }
        }
        
        // 处理一次性监听器
        foreach ($this->oneTimeListeners as $pattern => $patternListeners) {
            if ($pattern === $eventName || $pattern === '*' || $this->matchesWildcard($eventName, $pattern)) {
                foreach ($patternListeners as $listener) {
                    $listener($event);
                }
                
                // 移除这些一次性监听器
                unset($this->oneTimeListeners[$pattern]);
            }
        }
    }
    
    /**
     * 调用指定事件名称的所有监听器
     *
     * @param string $eventName 事件名称
     * @param EventInterface $event 事件对象
     * @return void
     */
    private function callListeners(string $eventName, EventInterface $event): void
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($event);
            }
        }
    }
    
    /**
     * 检查事件名称是否匹配通配符模式
     *
     * @param string $eventName 事件名称
     * @param string $pattern 模式，可包含通配符(*)
     * @return bool 是否匹配
     */
    private function matchesWildcard(string $eventName, string $pattern): bool
    {
        // 处理通配符前缀 (*.suffix)
        if (substr($pattern, 0, 2) === '*.') {
            $suffix = substr($pattern, 1);
            return substr($eventName, -strlen($suffix)) === $suffix;
        }
        
        // 处理通配符后缀 (prefix.*)
        if (substr($pattern, -2) === '.*') {
            $prefix = substr($pattern, 0, -1);
            return substr($eventName, 0, strlen($prefix)) === $prefix;
        }
        
        return false;
    }
    
    /**
     * 清除所有事件监听器
     *
     * @return void
     */
    public function clearEventListeners(): void
    {
        $this->listeners = [];
        $this->oneTimeListeners = [];
    }
    
    /**
     * 清除特定事件的所有监听器
     *
     * @param string $eventName 事件名称
     * @return void
     */
    public function clearEventListenersForEvent(string $eventName): void
    {
        unset($this->listeners[$eventName]);
        unset($this->oneTimeListeners[$eventName]);
    }
} 