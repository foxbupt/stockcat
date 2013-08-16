/**
 * @desc: 实现抓取股票的实时交易数据
 * @author: fox
 * @date: 2013/08/07
 */

package main

import (// "net/http" 
        "fmt"
        // "log"
        "strconv"
        // "strings"
        "os"
        "flag"
        // "time"
        "redigo/redis"
       )

// 获取数据的定时器
// var dataTicket time.Ticker
// 计时的定时器
// var clockTicket time.Ticker

func init() {
    flag.Usage = func() {
        fmt.Fprintf(os.Stderr, "Usage: %s <ip> <port> <key> [interval]\n", os.Args[0])
        flag.PrintDefaults()
    }
}

func getStockList(ip string, port int, key string) interface{} {
    // 从redis中获取待抓取的股票列表
    conn, err := redis.Dial("tcp", ip + ":" + strconv.Itoa(port))
    if err != nil {
        fmt.Printf("err=connect_redis ip=%s port=%d\n", ip, port)
        return err
    }

    reply, err2 := conn.Do("SMEMBERS", key)
    if err2 != nil {
        fmt.Printf("err=redis_scard ip=%s port=%d key=%s\n", ip, port, key)
        return err2
    }

    list, ok := ([]interface{})reply
    if !ok {
        fmt.Println("error")
    }
    for i:=0; i < len(list); i++ {
        value, ok1 := list[i].(string)
        fmt.Printf("%s\n", value)
    }
    fmt.Printf("reply=%v len=%d\n", reply, len(list))
    return reply
}

func main() {
    var ip = flag.String("ip", "127.0.0.1", "redis server ip")
    var port = flag.Int("port", 6379, "redis server port")
    var key = flag.String("key", "stock:pool", "stock pool list")
    var interval = flag.Int("interval", 60, "fetch interval")
    fmt.Printf("interval=%d\n", *interval)
    flag.Parse()

    if (len(*key) <= 0) {
        fmt.Printf("err=empty_key key=%s", *key)
        os.Exit(1)
    }

    getStockList(*ip, *port, *key)
        

}
