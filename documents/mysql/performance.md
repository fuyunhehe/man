# Menu

- [优化mysql性能]
    + [设置cache](#设置cache)
    + [增加读写线程数](#增加读写线程数)

# 优化mysql性能

## 设置cache

> 当MySQL开启了缓存模式（query_cache_type=1）后，mysql会把查询语句和查询结果保存在一张hash表中，下一次用同样的sql语句查询时，mysql会先从这张hash表中获取数据，如果缓存没有命中，则解析sql语句，查询数据库。 当缓存的数据达到最大值（query_cache_size） 后，mysql会把老的数据删除掉，写入信的数据。

通过以下命令查看设置结果
```mysql
MariaDB [(none)]> show variables like '%query_cache%';
+------------------------------+---------+
| Variable_name                | Value   |
+------------------------------+---------+
| have_query_cache             | YES     |
| query_cache_limit            | 1048576 |
| query_cache_min_res_unit     | 4096    |
| query_cache_size             | 1048576 |
| query_cache_strip_comments   | OFF     |
| query_cache_type             | DEMAND  |
| query_cache_wlock_invalidate | OFF     |
+------------------------------+---------+

MariaDB [(none)]> show status like '%Qcache%';
+-------------------------+---------+
| Variable_name           | Value   |
+-------------------------+---------+
| Qcache_free_blocks      | 1       |
| Qcache_free_memory      | 1031320 |
| Qcache_hits             | 0       |
| Qcache_inserts          | 0       |
| Qcache_lowmem_prunes    | 0       |
| Qcache_not_cached       | 1106663 |
| Qcache_queries_in_cache | 0       |
| Qcache_total_blocks     | 1       |
+-------------------------+---------+
```

[Menu](#menu)

## 增加读写线程数

编辑`mysql.ini`:
> innodb_read_io_threads=16
innodb_read_write_threads=16

