<?php

declare(strict_types=1);

namespace Tourze\NES\Bus\Memory;

/**
 * 地址解码器类
 * 
 * 负责将虚拟地址映射到实际地址，处理地址镜像和映射
 */
class AddressDecoder
{
    /**
     * 映射规则列表
     * 
     * @var array
     */
    private array $rules = [];
    
    /**
     * 规则ID计数器
     * 
     * @var int
     */
    private int $ruleIdCounter = 0;
    
    /**
     * 添加通用映射规则
     *
     * @param int $startAddress 源起始地址
     * @param int $endAddress 源结束地址
     * @param int $targetStartAddress 目标起始地址
     * @param callable $mapperFunction 映射函数，接受源地址返回目标地址
     * @param int $priority 优先级，数字越大优先级越高
     * @return int 规则ID，可用于后续移除规则
     */
    public function addMappingRule(
        int $startAddress,
        int $endAddress,
        int $targetStartAddress,
        callable $mapperFunction,
        int $priority = 0
    ): int {
        $ruleId = $this->ruleIdCounter++;
        
        $this->rules[$ruleId] = [
            'start' => $startAddress,
            'end' => $endAddress,
            'targetStart' => $targetStartAddress,
            'mapper' => $mapperFunction,
            'priority' => $priority,
        ];
        
        // 按优先级排序，高优先级在前
        uasort($this->rules, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $ruleId;
    }
    
    /**
     * 添加镜像规则
     *
     * @param int $startAddress 区域起始地址
     * @param int $endAddress 区域结束地址
     * @param int $mirrorSize 镜像大小
     * @param int $priority 优先级，数字越大优先级越高
     * @return int 规则ID，可用于后续移除规则
     */
    public function addMirrorRule(
        int $startAddress,
        int $endAddress,
        int $mirrorSize,
        int $priority = 0
    ): int {
        return $this->addMappingRule(
            $startAddress,
            $endAddress,
            $startAddress,
            function (int $sourceAddress) use ($startAddress, $mirrorSize): int {
                // 计算相对于区域起始地址的偏移量
                $offset = $sourceAddress - $startAddress;
                
                // 使用模运算找到在镜像大小内的偏移量
                $mirroredOffset = $offset % $mirrorSize;
                
                // 返回基础地址加镜像后的偏移量
                return $startAddress + $mirroredOffset;
            },
            $priority
        );
    }
    
    /**
     * 移除规则
     *
     * @param int $ruleId 要移除的规则ID
     * @return bool 是否成功移除
     */
    public function removeRule(int $ruleId): bool
    {
        if (isset($this->rules[$ruleId])) {
            unset($this->rules[$ruleId]);
            return true;
        }
        
        return false;
    }
    
    /**
     * 清除所有规则
     *
     * @return void
     */
    public function clearRules(): void
    {
        $this->rules = [];
    }
    
    /**
     * 解码地址
     *
     * @param int $address 输入地址
     * @return int 解码后的地址
     */
    public function decodeAddress(int $address): int
    {
        foreach ($this->rules as $rule) {
            if ($address >= $rule['start'] && $address <= $rule['end']) {
                return ($rule['mapper'])($address);
            }
        }
        
        // 如果没有匹配的规则，返回原始地址
        return $address;
    }
    
    /**
     * 获取所有规则
     *
     * @return array 规则列表
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
