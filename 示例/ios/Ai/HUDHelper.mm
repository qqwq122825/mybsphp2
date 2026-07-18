#import <spawn.h>
#import <notify.h>
#import <mach-o/dyld.h>
#import "HUDHelper.h"
#import "NSUserDefaults+Private.h"

static const char *ExecutablePath(void) {
    static char *executablePath = NULL;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        uint32_t size = 0;
        _NSGetExecutablePath(NULL, &size);
        executablePath = (char *)calloc(1, size);
        _NSGetExecutablePath(executablePath, &size);
    });
    return executablePath;
}
//
//  HUDHelper.mm

//
//  Created by Lessica on 2024/1/24.
//

#import <spawn.h>
#import <notify.h>
#import <mach-o/dyld.h>

#import "HUDHelper.h"
#import "NSUserDefaults+Private.h"

extern "C" char **environ;

#define POSIX_SPAWN_PERSONA_FLAGS_OVERRIDE 1
extern "C" int posix_spawnattr_set_persona_np(const posix_spawnattr_t* __restrict, uid_t, uint32_t);
extern "C" int posix_spawnattr_set_persona_uid_np(const posix_spawnattr_t* __restrict, uid_t);
extern "C" int posix_spawnattr_set_persona_gid_np(const posix_spawnattr_t* __restrict, uid_t);

BOOL IsHUDEnabled(void)
{
    posix_spawnattr_t attr;
    posix_spawnattr_init(&attr);

    posix_spawnattr_set_persona_np(&attr, 99, POSIX_SPAWN_PERSONA_FLAGS_OVERRIDE);
    posix_spawnattr_set_persona_uid_np(&attr, 0);
    posix_spawnattr_set_persona_gid_np(&attr, 0);

    pid_t task_pid;
    const char *args[] = { ExecutablePath(), "-check", NULL };
    posix_spawn(&task_pid, ExecutablePath(), NULL, &attr, (char **)args, environ);
    posix_spawnattr_destroy(&attr);
   
    int status;
    do {
        if (waitpid(task_pid, &status, 0) != -1)
        {
        
        }
    } while (!WIFEXITED(status) && !WIFSIGNALED(status));

    return WEXITSTATUS(status) != 0;
}



void SetHUDEnabled(BOOL isEnabled)
{
    posix_spawnattr_t attr;
    posix_spawnattr_init(&attr);

    posix_spawnattr_set_persona_np(&attr, 99, POSIX_SPAWN_PERSONA_FLAGS_OVERRIDE);
    posix_spawnattr_set_persona_uid_np(&attr, 0);
    posix_spawnattr_set_persona_gid_np(&attr, 0);

    if (isEnabled)
    {
        posix_spawnattr_setpgroup(&attr, 0);
        posix_spawnattr_setflags(&attr, POSIX_SPAWN_SETPGROUP);

        pid_t task_pid;
        const char *args[] = { ExecutablePath(), "-hud", NULL };
        posix_spawn(&task_pid, ExecutablePath(), NULL, &attr, (char **)args, environ);
        posix_spawnattr_destroy(&attr);
    }
    else
    {
        [NSThread sleepForTimeInterval:0.35];

        pid_t task_pid;
        const char *args[] = { ExecutablePath(), "-exit", NULL };
        posix_spawn(&task_pid, ExecutablePath(), NULL, &attr, (char **)args, environ);
        posix_spawnattr_destroy(&attr);

        int status;
        do {
            if (waitpid(task_pid, &status, 0) != -1)
            {
            }
        } while (!WIFEXITED(status) && !WIFSIGNALED(status));
    }
}

