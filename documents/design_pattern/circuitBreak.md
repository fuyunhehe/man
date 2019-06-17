# Menu

- [定义](#定义)
- [实现原理](#实现原理)
    + [数据结构](#数据结构)

## 定义

**熔断器模式** 
在分布式环境中，我们的应用可能会面临着各种各样的可恢复的异常（比如超时，网络环境异常），此时我们可以利用不断重试的方式来从异常中恢复(Retry Pattern)，使整个集群正常运行。

然而，也有一些异常比较顽固，突然发生，无法预测，而且很难恢复，并且还会导致级联失败（举个例子，假设一个服务集群的负载非常高，如果这时候集群的一部分挂掉了，还占了很大一部分资源，整个集群都有可能遭殃）。如果我们这时还是不断进行重试的话，结果大多都是失败的。因此，此时我们的应用需要立即进入失败状态(fast-fail)，并采取合适的方法进行恢复。

Circuit Breaker Pattern（熔断器模式）就是这样的一种设计思想。它可以防止一个应用不断地去尝试一个很可能失败的操作。一个Circuit Breaker相当于一个代理，用于监测某个操作对应的失败比率(fail / fail + success)。它会根据得到的数据来决定是否允许执行此操作，或者是立即抛出异常。

## 实现原理

### 数据结构

```go
type State int

const (
    StateClosed State = iota    // 未开启
    StateHalfOpen   // 触发异常，中间状态，若持续失败将变为 StateOpen
    StateOpen   // 已开启
)

type Counts struct{
    Requests             uint32
    TotalSuccesses       uint32
    TotalFailures        uint32
    ConsecutiveSuccesses uint32
    ConsecutiveFailures  uint32
}

type CircuitBreak struct {
    maxRequests   uint32    // StateHalfOpen 状态下，允许最大的请求数
    interval      time.Duration // 每一轮的时间间隔
    timeout       time.Duration // 断路开启时间
    readyToTrip   func(counts Counts) bool  // 判断是否开启断路函数
    onStateChange func(name string, from State, to State)

    mutex      sync.Mutex
    state      State    // 熔断器开启状态
    generation uint64   // 每一轮的标识
    counts     Counts   // 每轮的统计
    expiry     time.Time
}
```

### 处理流程

**熔断器模式**的流程主要分未两个阶段：beforeRequest、afterRequest

1. beforeRequest
每次接收到请求时，判断熔断器的状态 state，熔断器状态可能为以下情形：
- StateClosed
熔断器关闭，表明一切正常，服务该次请求
- StateOpen
熔断器已开启，判断`expiry`是否过期，若未过期，则直接拒绝服务；否则，将状态改为`StateHalfOpen`
- StateHalfOpen
熔断器半开启，判断请求数是否大于`maxRequests`，若是，则直接拒绝服务；否则，服务该次请求

代码如下：
```go
func (cb *CircuitBreaker) beforeRequest() (uint64, error) {
    cb.mutex.Lock()
    defer cb.mutex.Unlock()

    now := time.Now()
    state, generation := cb.currentState(now)

    if state == StateOpen {
        return generation, ErrOpenState
    } else if state == StateHalfOpen && cb.counts.Requests >= cb.maxRequests {
        return generation, ErrTooManyRequests
    }

    cb.counts.onRequest()
    return generation, nil
}
```

2. afterRequest
此时熔断器的状态只可能为`StateClosed`、`StateHalfOpen`。
服务逻辑处理请求之后，熔断器根据处理请求结果是否`error`，更新熔断器的状态。
- error == nil
判断熔断器当前状态
    - StateClosed
    无需处理
    - StateHalfOpen
    若`counts.ConsecutiveSuccesses > maxRequests`则，将状态置为`StateClosed`
- error != nil
判断熔断器当前状态    
    - StateClosed
    根据`readyToTrip`处理结果，判断是否将状态置为`StateHalfOpen`
    - StateHalfOpen
    将状态置为`StateOpen`

代码如下：
```go
func (cb *CircuitBreaker) afterRequest(before uint64, success bool) {
    cb.mutex.Lock()
    defer cb.mutex.Unlock()

    now := time.Now()
    state, generation := cb.currentState(now)
    if generation != before {
        return
    }

    if success {
        cb.onSuccess(state, now)
    } else {
        cb.onFailure(state, now)
    }
}

func (cb *CircuitBreaker) onSuccess(state State, now time.Time) {
    switch state {
    case StateClosed:
        cb.counts.onSuccess()
    case StateHalfOpen:
        cb.counts.onSuccess()
        if cb.counts.ConsecutiveSuccesses >= cb.maxRequests {
            cb.setState(StateClosed, now)
        }
    }
}

func (cb *CircuitBreaker) onFailure(state State, now time.Time) {
    switch state {
    case StateClosed:
        cb.counts.onFailure()
        if cb.readyToTrip(cb.counts) {
            cb.setState(StateOpen, now)
        }
    case StateHalfOpen:
        cb.setState(StateOpen, now)
    }
}
```
