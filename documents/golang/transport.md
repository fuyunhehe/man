# Menu

- [详解](#详解)
    + [结构](#结构)
    + [连接池](#连接池)
- [问题分析](#问题分析)
    + [golang连接池失效](#golang连接池失效)

## 详解

详解过程中的源码，只贴出了重点代码，且与https相关的均省略了

### 结构

```
var DefaultTransport RoundTripper = &Transport{
    Proxy: ProxyFromEnvironment,
    DialContext: (&net.Dialer{
        Timeout:   30 * time.Second,
        KeepAlive: 30 * time.Second,
        DualStack: true,
    }).DialContext,
    MaxIdleConns:          100,
    IdleConnTimeout:       90 * time.Second,
    TLSHandshakeTimeout:   10 * time.Second,
    ExpectContinueTimeout: 1 * time.Second,
}

type Transport struct {
    idleMu     sync.Mutex
    wantIdle   bool                                // user has requested to close all idle conns
    idleConn   map[connectMethodKey][]*persistConn // most recently used at end
    idleConnCh map[connectMethodKey]chan *persistConn
    idleLRU    connLRU

    reqMu       sync.Mutex
    reqCanceler map[*Request]func(error)

    altMu    sync.Mutex   // guards changing altProto only
    altProto atomic.Value // of nil or map[string]RoundTripper, key is URI scheme

    // Proxy specifies a function to return a proxy for a given
    // Request. If the function returns a non-nil error, the
    // request is aborted with the provided error.
    //
    // The proxy type is determined by the URL scheme. "http"
    // and "socks5" are supported. If the scheme is empty,
    // "http" is assumed.
    //
    // If Proxy is nil or returns a nil *URL, no proxy is used.
    Proxy func(*Request) (*url.URL, error)

    // DialContext specifies the dial function for creating unencrypted TCP connections.
    // If DialContext is nil (and the deprecated Dial below is also nil),
    // then the transport dials using package net.
    DialContext func(ctx context.Context, network, addr string) (net.Conn, error)

    // Dial specifies the dial function for creating unencrypted TCP connections.
    //
    // Deprecated: Use DialContext instead, which allows the transport
    // to cancel dials as soon as they are no longer needed.
    // If both are set, DialContext takes priority.
    Dial func(network, addr string) (net.Conn, error)

    // DialTLS specifies an optional dial function for creating
    // TLS connections for non-proxied HTTPS requests.
    //
    // If DialTLS is nil, Dial and TLSClientConfig are used.
    //
    // If DialTLS is set, the Dial hook is not used for HTTPS
    // requests and the TLSClientConfig and TLSHandshakeTimeout
    // are ignored. The returned net.Conn is assumed to already be
    // past the TLS handshake.
    DialTLS func(network, addr string) (net.Conn, error)

    // TLSClientConfig specifies the TLS configuration to use with
    // tls.Client.
    // If nil, the default configuration is used.
    // If non-nil, HTTP/2 support may not be enabled by default.
    TLSClientConfig *tls.Config

    // TLSHandshakeTimeout specifies the maximum amount of time waiting to
    // wait for a TLS handshake. Zero means no timeout.
    TLSHandshakeTimeout time.Duration

    // DisableKeepAlives, if true, prevents re-use of TCP connections
    // between different HTTP requests.
    DisableKeepAlives bool

    // DisableCompression, if true, prevents the Transport from
    // requesting compression with an "Accept-Encoding: gzip"
    // request header when the Request contains no existing
    // Accept-Encoding value. If the Transport requests gzip on
    // its own and gets a gzipped response, it's transparently
    // decoded in the Response.Body. However, if the user
    // explicitly requested gzip it is not automatically
    // uncompressed.
    DisableCompression bool

    // MaxIdleConns controls the maximum number of idle (keep-alive)
    // connections across all hosts. Zero means no limit.
    MaxIdleConns int

    // MaxIdleConnsPerHost, if non-zero, controls the maximum idle
    // (keep-alive) connections to keep per-host. If zero,
    // DefaultMaxIdleConnsPerHost is used.
    MaxIdleConnsPerHost int

    // IdleConnTimeout is the maximum amount of time an idle
    // (keep-alive) connection will remain idle before closing
    // itself.
    // Zero means no limit.
    IdleConnTimeout time.Duration

    // ResponseHeaderTimeout, if non-zero, specifies the amount of
    // time to wait for a server's response headers after fully
    // writing the request (including its body, if any). This
    // time does not include the time to read the response body.
    ResponseHeaderTimeout time.Duration

    // ExpectContinueTimeout, if non-zero, specifies the amount of
    // time to wait for a server's first response headers after fully
    // writing the request headers if the request has an
    // "Expect: 100-continue" header. Zero means no timeout and
    // causes the body to be sent immediately, without
    // waiting for the server to approve.
    // This time does not include the time to send the request header.
    ExpectContinueTimeout time.Duration

    // TLSNextProto specifies how the Transport switches to an
    // alternate protocol (such as HTTP/2) after a TLS NPN/ALPN
    // protocol negotiation. If Transport dials an TLS connection
    // with a non-empty protocol name and TLSNextProto contains a
    // map entry for that key (such as "h2"), then the func is
    // called with the request's authority (such as "example.com"
    // or "example.com:1234") and the TLS connection. The function
    // must return a RoundTripper that then handles the request.
    // If TLSNextProto is not nil, HTTP/2 support is not enabled
    // automatically.
    TLSNextProto map[string]func(authority string, c *tls.Conn) RoundTripper

    // ProxyConnectHeader optionally specifies headers to send to
    // proxies during CONNECT requests.
    ProxyConnectHeader Header

    // MaxResponseHeaderBytes specifies a limit on how many
    // response bytes are allowed in the server's response
    // header.
    //
    // Zero means to use a default limit.
    MaxResponseHeaderBytes int64

    // nextProtoOnce guards initialization of TLSNextProto and
    // h2transport (via onceSetNextProtoDefaults)
    nextProtoOnce sync.Once
    h2transport   *http2Transport // non-nil if http2 wired up

    // TODO: tunable on max per-host TCP dials in flight (Issue 13957)
}

// persistConn wraps a connection, usually a persistent one
// (but may be used for non-keep-alive requests as well)
type persistConn struct {
    // alt optionally specifies the TLS NextProto RoundTripper.
    // This is used for HTTP/2 today and future protocols later.
    // If it's non-nil, the rest of the fields are unused.
    alt RoundTripper

    // 该连接所属的transport指针
    t         *Transport
    cacheKey  connectMethodKey
    conn      net.Conn
    tlsState  *tls.ConnectionState
    br        *bufio.Reader       // from conn
    bw        *bufio.Writer       // to conn
    nwrite    int64               // bytes written
    reqch     chan requestAndChan // written by roundTrip; read by readLoop
    writech   chan writeRequest   // written by roundTrip; read by writeLoop
    closech   chan struct{}       // closed when conn closed
    isProxy   bool
    sawEOF    bool  // whether we've seen EOF from conn; owned by readLoop
    readLimit int64 // bytes allowed to be read; owned by readLoop
    // writeErrCh passes the request write error (usually nil)
    // from the writeLoop goroutine to the readLoop which passes
    // it off to the res.Body reader, which then uses it to decide
    // whether or not a connection can be reused. Issue 7569.
    writeErrCh chan error

    writeLoopDone chan struct{} // closed when write loop ends

    // Both guarded by Transport.idleMu:
    idleAt    time.Time   // time it last become idle
    idleTimer *time.Timer // holding an AfterFunc to close it

    mu                   sync.Mutex // guards following fields
    numExpectedResponses int
    closed               error // set non-nil when conn is closed, before closech is closed
    canceledErr          error // set non-nil if conn is canceled
    broken               bool  // an error has happened on this connection; marked broken so it's not reused.
    reused               bool  // whether conn has had successful request/response and is being reused.
    // mutateHeaderFunc is an optional func to modify extra
    // headers on each outbound request before it's written. (the
    // original Request given to RoundTrip is not modified)
    mutateHeaderFunc func(Header)
}
```

### 连接池

- getConn获取链接

从连接池中获取未用连接，或新建连接，作为函数返回
```
// getConn dials and creates a new persistConn to the target as
// specified in the connectMethod. This includes doing a proxy CONNECT
// and/or setting up TLS.  If this doesn't return an error, the persistConn
// is ready to write requests to.
func (t *Transport) getConn(treq *transportRequest, cm connectMethod) (*persistConn, error) {
    // 从 idleConn 连接池中获取空闲连接
    if pc, idleSince := t.getIdleConn(cm); pc != nil {
        return pc, nil
    }

    // 无空闲连接
    type dialRes struct {
        pc  *persistConn
        err error
    }
    dialc := make(chan dialRes)

    // 已有连接变为空闲时，新创建连接由下面的函数进行处理
    // 返回的连接可能是新建的，也可能是已有连接变为空闲状态
    handlePendingDial := func() {
        go func() {
            if v := <-dialc; v.err == nil {
                t.putOrCloseIdleConn(v.pc)
            }
        }()
    }

    // 创建新的连接
    go func() {
        // dialConn后边会重点介绍
        pc, err := t.dialConn(ctx, cm)
        dialc <- dialRes{pc, err}
    }()

    // 新连接创建 或 已有连接变为空闲 
    // 哪个先就绪则返回
    // idleConnCh 该channel是一个临时的，随时可能删除，且无缓冲
    idleConnCh := t.getIdleConnCh(cm)
    select {
    // 新建连接已经完成
    case v := <-dialc:
        // Our dial finished.
        if v.pc != nil {
            return v.pc, nil
        }
        // Our dial failed. See why to return a nicer error
        // value.
        return nil, v.err
    // 已有连接变为空闲
    case pc := <-idleConnCh:
        // Another request finished first and its net.Conn
        // became available before our dial. Or somebody
        // else's dial that they didn't use.
        // But our dial is still going, so give it away
        // when it finishes:
        handlePendingDial()
        if trace != nil && trace.GotConn != nil {
            trace.GotConn(httptrace.GotConnInfo{Conn: pc.conn, Reused: pc.isReused()})
        }
        return pc, nil
}
```

从map中空闲连接数组中，获取空闲连接，同时将返回的连接从空闲连接数组中移出
```
func (t *Transport) getIdleConn(cm connectMethod) (pconn *persistConn, idleSince time.Time) {
    key := cm.key()
    t.idleMu.Lock()
    defer t.idleMu.Unlock()
    for {
        pconns, ok := t.idleConn[key]
        if !ok {
            return nil, time.Time{}
        }
        ...
        pconn = pconns[len(pconns)-1]
        t.idleConn[key] = pconns[:len(pconns)-1]
        ...
        return pconn, pconn.idleAt
    }
}
```

将新创建的连接，尝试放入空闲连接数组中；若空闲连接过多，触发LRU机制
```
func (t *Transport) putOrCloseIdleConn(pconn *persistConn) {
    if err := t.tryPutIdleConn(pconn); err != nil {
        pconn.close(err)
    }
}

// tryPutIdleConn adds pconn to the list of idle persistent connections awaiting
// a new request.
// If pconn is no longer needed or not in a good state, tryPutIdleConn returns
// an error explaining why it wasn't registered.
// tryPutIdleConn does not close pconn. Use putOrCloseIdleConn instead for that.
func (t *Transport) tryPutIdleConn(pconn *persistConn) error {
    key := pconn.cacheKey

    t.idleMu.Lock()
    defer t.idleMu.Unlock()

    waitingDialer := t.idleConnCh[key]
    select {
    case waitingDialer <- pconn:
        // 其他人等待该类型连接，因此得到复用
        return nil
    default:
        if waitingDialer != nil {
            // They had populated this, but their dial won
            // first, so we can clean up this map entry.
            delete(t.idleConnCh, key)
        }
    }
    idles := t.idleConn[key]
    if len(idles) >= t.maxIdleConnsPerHost() {
        return errTooManyIdleHost
    }
    t.idleConn[key] = append(idles, pconn)
    t.idleLRU.add(pconn)
    if t.MaxIdleConns != 0 && t.idleLRU.len() > t.MaxIdleConns {
        oldest := t.idleLRU.removeOldest()
        oldest.close(errTooManyIdle)
        t.removeIdleConnLocked(oldest)
    }
    if t.IdleConnTimeout > 0 {
        if pconn.idleTimer != nil {
            pconn.idleTimer.Reset(t.IdleConnTimeout)
        } else {
            pconn.idleTimer = time.AfterFunc(t.IdleConnTimeout, pconn.closeConnIfStillIdle)
        }
    }
    pconn.idleAt = time.Now()
    return nil
}
```

- RoundTrip 发起请求

```
// RoundTrip implements the RoundTripper interface.
//
// For higher-level HTTP client support (such as handling of cookies
// and redirects), see Get, Post, and the Client type.
func (t *Transport) RoundTrip(req *Request) (*Response, error) {
    // 协议转换
    ...

    // 
    for {
        // treq gets modified by roundTrip, so we need to recreate for each retry.
        treq := &transportRequest{Request: req, trace: trace}
        cm, err := t.connectMethodForRequest(treq)
        if err != nil {
            req.closeBody()
            return nil, err
        }

        // Get the cached or newly-created connection to either the
        // host (for http or https), the http proxy, or the http proxy
        // pre-CONNECTed to https server. In any case, we'll be ready
        // to send it requests.
        pconn, err := t.getConn(treq, cm)
        if err != nil {
            t.setReqCanceler(req, nil)
            req.closeBody()
            return nil, err
        }

        var resp *Response
        // 发出请求
        resp, err = pconn.roundTrip(treq)
        if err == nil {
            return resp, nil
        }

        // 重试&返回错误
        ...
    }
}
```

persistConn.roundTrip
```
func (pc *persistConn) roundTrip(req *transportRequest) (resp *Response, err error) {
    pc.mu.Lock()
    pc.numExpectedResponses++
    headerFn := pc.mutateHeaderFunc
    pc.mu.Unlock()

    var continueCh chan struct{}
    if req.ProtoAtLeast(1, 1) && req.Body != nil && req.expectsContinue() {
        continueCh = make(chan struct{}, 1)
    }

    gone := make(chan struct{})
    defer close(gone)

    // Write the request concurrently with waiting for a response,
    // in case the server decides to reply before reading our full
    // request body.
    startBytesWritten := pc.nwrite
    writeErrCh := make(chan error, 1)
    pc.writech <- writeRequest{req, writeErrCh, continueCh}

    resc := make(chan responseAndError)
    pc.reqch <- requestAndChan{
        req:        req.Request,
        ch:         resc,
        addedGzip:  requestedGzip,
        continueCh: continueCh,
        callerGone: gone,
    }

    // 建立连接后，头部超时计时器，writeErrCh接收到数据后，err为nil会触发
    var respHeaderTimer <-chan time.Time
    for {
        select {
        case err := <-writeErrCh:
            // if err != nil return
            ...
            // if err == nil
            if d := pc.t.ResponseHeaderTimeout; d > 0 {
                if debugRoundTrip {
                    req.logf("starting timer for %v", d)
                }
                timer := time.NewTimer(d)
                defer timer.Stop() // prevent leaks
                respHeaderTimer = timer.C
            }
        case <-pc.closech:
            return nil, pc.mapRoundTripError(req, startBytesWritten, pc.closed)
        case <-respHeaderTimer:
            pc.close(errTimeout)
            return nil, errTimeout
        case re := <-resc:
            // 若err为nil，则res必须有值，且该值即为resp
            ...
            if re.err != nil {
                return nil, pc.mapRoundTripError(req, startBytesWritten, re.err)
            }
            return re.res, nil
        }
    }
}
```
上面代码中有几个重要的channel：`writech`、`reqch`、`closech`，由异步gorouting进行写入值

- dialConn创建连接

```
func (t *Transport) dialConn(ctx context.Context, cm connectMethod) (*persistConn, error) {
    pconn := &persistConn{
        t:             t,
        cacheKey:      cm.key(),
        reqch:         make(chan requestAndChan, 1),
        writech:       make(chan writeRequest, 1),
        closech:       make(chan struct{}),
        writeErrCh:    make(chan error, 1),
        writeLoopDone: make(chan struct{}),
    }
    trace := httptrace.ContextClientTrace(ctx)
    tlsDial := t.DialTLS != nil && cm.targetScheme == "https" && cm.proxyURL == nil
    if tlsDial {
        var err error
        pconn.conn, err = t.DialTLS("tcp", cm.addr())
        if err != nil {
            return nil, err
        }
        if pconn.conn == nil {
            return nil, errors.New("net/http: Transport.DialTLS returned (nil, nil)")
        }
        if tc, ok := pconn.conn.(*tls.Conn); ok {
            // Handshake here, in case DialTLS didn't. TLSNextProto below
            // depends on it for knowing the connection state.
            if trace != nil && trace.TLSHandshakeStart != nil {
                trace.TLSHandshakeStart()
            }
            if err := tc.Handshake(); err != nil {
                go pconn.conn.Close()
                if trace != nil && trace.TLSHandshakeDone != nil {
                    trace.TLSHandshakeDone(tls.ConnectionState{}, err)
                }
                return nil, err
            }
            cs := tc.ConnectionState()
            if trace != nil && trace.TLSHandshakeDone != nil {
                trace.TLSHandshakeDone(cs, nil)
            }
            pconn.tlsState = &cs
        }
    } else {
        conn, err := t.dial(ctx, "tcp", cm.addr())
        if err != nil {
            if cm.proxyURL != nil {
                // Return a typed error, per Issue 16997:
                err = &net.OpError{Op: "proxyconnect", Net: "tcp", Err: err}
            }
            return nil, err
        }
        pconn.conn = conn
    }

    // Proxy setup.
    switch {
    case cm.proxyURL == nil:
        // Do nothing. Not using a proxy.
    case cm.proxyURL.Scheme == "socks5":
        conn := pconn.conn
        var auth *proxy.Auth
        if u := cm.proxyURL.User; u != nil {
            auth = &proxy.Auth{}
            auth.User = u.Username()
            auth.Password, _ = u.Password()
        }
        p, err := proxy.SOCKS5("", cm.addr(), auth, newOneConnDialer(conn))
        if err != nil {
            conn.Close()
            return nil, err
        }
        if _, err := p.Dial("tcp", cm.targetAddr); err != nil {
            conn.Close()
            return nil, err
        }
    case cm.targetScheme == "http":
        pconn.isProxy = true
        if pa := cm.proxyAuth(); pa != "" {
            pconn.mutateHeaderFunc = func(h Header) {
                h.Set("Proxy-Authorization", pa)
            }
        }
    case cm.targetScheme == "https":
        conn := pconn.conn
        hdr := t.ProxyConnectHeader
        if hdr == nil {
            hdr = make(Header)
        }
        connectReq := &Request{
            Method: "CONNECT",
            URL:    &url.URL{Opaque: cm.targetAddr},
            Host:   cm.targetAddr,
            Header: hdr,
        }
        if pa := cm.proxyAuth(); pa != "" {
            connectReq.Header.Set("Proxy-Authorization", pa)
        }
        connectReq.Write(conn)

        // Read response.
        // Okay to use and discard buffered reader here, because
        // TLS server will not speak until spoken to.
        br := bufio.NewReader(conn)
        resp, err := ReadResponse(br, connectReq)
        if err != nil {
            conn.Close()
            return nil, err
        }
        if resp.StatusCode != 200 {
            f := strings.SplitN(resp.Status, " ", 2)
            conn.Close()
            return nil, errors.New(f[1])
        }
    }

    if cm.targetScheme == "https" && !tlsDial {
        // Initiate TLS and check remote host name against certificate.
        cfg := cloneTLSConfig(t.TLSClientConfig)
        if cfg.ServerName == "" {
            cfg.ServerName = cm.tlsHost()
        }
        plainConn := pconn.conn
        tlsConn := tls.Client(plainConn, cfg)
        errc := make(chan error, 2)
        var timer *time.Timer // for canceling TLS handshake
        if d := t.TLSHandshakeTimeout; d != 0 {
            timer = time.AfterFunc(d, func() {
                errc <- tlsHandshakeTimeoutError{}
            })
        }
        go func() {
            if trace != nil && trace.TLSHandshakeStart != nil {
                trace.TLSHandshakeStart()
            }
            err := tlsConn.Handshake()
            if timer != nil {
                timer.Stop()
            }
            errc <- err
        }()
        if err := <-errc; err != nil {
            plainConn.Close()
            if trace != nil && trace.TLSHandshakeDone != nil {
                trace.TLSHandshakeDone(tls.ConnectionState{}, err)
            }
            return nil, err
        }
        if !cfg.InsecureSkipVerify {
            if err := tlsConn.VerifyHostname(cfg.ServerName); err != nil {
                plainConn.Close()
                return nil, err
            }
        }
        cs := tlsConn.ConnectionState()
        if trace != nil && trace.TLSHandshakeDone != nil {
            trace.TLSHandshakeDone(cs, nil)
        }
        pconn.tlsState = &cs
        pconn.conn = tlsConn
    }

    if s := pconn.tlsState; s != nil && s.NegotiatedProtocolIsMutual && s.NegotiatedProtocol != "" {
        if next, ok := t.TLSNextProto[s.NegotiatedProtocol]; ok {
            return &persistConn{alt: next(cm.targetAddr, pconn.conn.(*tls.Conn))}, nil
        }
    }

    pconn.br = bufio.NewReader(pconn)
    pconn.bw = bufio.NewWriter(persistConnWriter{pconn})
    go pconn.readLoop()
    go pconn.writeLoop()
    return pconn, nil
}
```

`writeLoop`中将另起协程读取`writech`中的值
```
func (pc *persistConn) writeLoop() {
    defer close(pc.writeLoopDone)
    for {
        select {
        case wr := <-pc.writech:
            startBytesWritten := pc.nwrite
            err := wr.req.Request.write(pc.bw, pc.isProxy, wr.req.extra, pc.waitForContinue(wr.continueCh))
            if bre, ok := err.(requestBodyReadError); ok {
                err = bre.error
                // Errors reading from the user's
                // Request.Body are high priority.
                // Set it here before sending on the
                // channels below or calling
                // pc.close() which tears town
                // connections and causes other
                // errors.
                wr.req.setError(err)
            }
            if err == nil {
                err = pc.bw.Flush()
            }
            if err != nil {
                wr.req.Request.closeBody()
                if pc.nwrite == startBytesWritten {
                    err = nothingWrittenError{err}
                }
            }
            pc.writeErrCh <- err // to the body reader, which might recycle us
            wr.ch <- err         // to the roundTrip function
            if err != nil {
                pc.close(err)
                return
            }
        case <-pc.closech:
            return
        }
    }
}
```

## 问题分析

### golang连接池失效

首先，连接池失效，在高并发大量请求场景下，会发起大量的http请求，但是，本想net/http 是支持长连接的，但是，几种情况，都产生了大量的time_wait，这里进行分析。
`ss -s | wc -l`可查看连接数

- 关闭了连接复用
若要开启连接复用，则`DisableKeepAlives`值必须为false，且`MaxIdleConns`与`MaxIdleConnsPerHost`必须大于0

- 误用transport
每次都new出新的transport
```
client.Transport = &http.Transport{
     Proxy: http.ProxyURL(proxyUrl)，
} //设置代理ip
```
trasnport中维护了两个map，暂存连接，如果每次都创建新的transport，则连接无法复用

- MaxIdleConnsPerHost & MaxIdleConns
transport中有连接池能够保留部分空闲连接复用，但是高频连接过多时，超过空闲连接处理能力时，transport会建立新的连接，这些连接处理请求之后，立马结束无法复用。此时，就会系统中就会产生大量timewait的tcp连接，当socket端口用完之后，系统后续连接将失败。
transport中有几个参数是控制连接池状态的，`MaxIdleConnsPerHost`设置单个host能够保留的最大复用连接数，`MaxIdleConns`设置整体能够保留的最大复用连接数。正确的设置是，评估好`MaxIdleConnsPerHost`和`MaxIdleConns`
```
// 若并发不超过2k，则通常设为2k就能覆盖所有并发请求
http.DefaultTransport.(*http.Transport).MaxIdleConnsPerHost = 2000
http.DefaultTransport.(*http.Transport).MaxIdleConns = 4096
http.DefaultTransport.(*http.Transport).IdleConnTimeout = 300 * time.Second
```

- resp.body未读取
resp.body 忘了读取，直接导致新请求会直接新建连接。其实可以理解，没read body 的socket， 如果直接复用，会产生什么样后果？所有使用这个套接字的连接都会错乱。
```
// 错误示例
package main

import (
  "fmt"
  "html"
  "log"
  "net"
  "net/http"
  "time"
)

func startWebserver() {

  http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
      fmt.Fprintf(w, "Hello, %q", html.EscapeString(r.URL.Path))
  })

  go http.ListenAndServe(":8080", nil)

}

func startLoadTest() {
  count := 0
  for {
      resp, err := http.Get("http://localhost:8080/")
      if err != nil {
          panic(fmt.Sprintf("Got error: %v", err))
      }
      resp.Body.Close()
      log.Printf("Finished GET request #%v", count)
      count += 1
  }

}

func main() {

  // start a webserver in a goroutine
  startWebserver()

  startLoadTest()

}
```
若不关心body，可以采用下列方式
`io.Copy(ioutil.Discard, resp.Body)`

