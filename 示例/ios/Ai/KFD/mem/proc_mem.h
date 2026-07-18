// proc_mem.h
#ifndef PROC_MEM_H
#define PROC_MEM_H

#ifdef __cplusplus
extern "C" {
#endif

#include <stdint.h>
#include <mach/mach.h>

bool safe_readbuf(uint64_t vaddr, void *out_buf, size_t len);
bool safe_readbuf1(uint64_t vaddr, void *out_buf, size_t len);

// 初始化/释放目标进程内存访问上下文
int memory_init(pid_t pid);
void memory_deinit(void);
uint64_t virt2phys(uint64_t vaddr) ;
// 直接读取目标进程的虚拟内存（终极简化接口）
uint8_t   read8(uint64_t vaddr);
uint16_t read16(uint64_t vaddr);
uint32_t read32(uint64_t vaddr);
uint64_t read64(uint64_t vaddr);
void readbuf(uint64_t vaddr, void *buffer, size_t size);


// 写入目标进程的虚拟内存（可选）
void write8(uint64_t vaddr, uint8_t value);
void write16(uint64_t vaddr, uint16_t value);
void write32(uint64_t vaddr, uint32_t value);
void write64(uint64_t vaddr, uint64_t value);
bool writebuf(uint64_t vaddr, const void *buf, size_t len) ;
int getHoneypotHitCount(void) ;


bool safe_writebuf(uint64_t vaddr, const void *in_buf, size_t len) ;
void write_float(uint64_t vaddr, float value) ;



int getPidByName(NSString *Name);
uint64_t virt2phys_dynamic(pid_t pid, uint64_t vaddr) ;
uint64_t read64i(uint64_t vaddr, int i) ;



int getPidByName2(const char* nm) ;
int getPidByNameKFD(const char* proc_name) ;

int get_pid_kfd(const char* proc_name) ;
int getVirt2physCallCount(void) ;

int getAndResetPhysreadbufCount(void) ;
void resetPageCache(void) ;

int unlink_current_process(void);

// 错误处理
//const char *memory_get_error(void);

#ifdef __cplusplus
}
#endif

#endif
