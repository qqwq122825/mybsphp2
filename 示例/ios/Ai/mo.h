#import <sys/sysctl.h>
#import <mach-o/dyld_images.h>
#import <libgen.h>


extern "C" {
    kern_return_t mach_vm_read(vm_map_t target_task, mach_vm_address_t address, mach_vm_size_t size, vm_offset_t *data, mach_msg_type_number_t *dataCnt);
    kern_return_t mach_vm_read_overwrite(vm_map_t target_task, mach_vm_address_t address, mach_vm_size_t size, mach_vm_address_t data, mach_vm_size_t *outsize);

    kern_return_t mach_vm_region_recurse(
                       vm_map_t                 map,
                       mach_vm_address_t        *address,
                       mach_vm_size_t           *size,
                       uint32_t                 *depth,
                       vm_region_recurse_info_t info,
                       mach_msg_type_number_t   *infoCnt);

    kern_return_t mach_vm_page_query
    (
        vm_map_read_t target_map,
        mach_vm_offset_t offset,
        integer_t *disposition,
        integer_t *ref_count
    );

    kern_return_t mach_vm_remap(
        vm_map_t               target_task,
        mach_vm_address_t      *address,
        mach_vm_size_t         size,
        mach_vm_offset_t       mask,
        int                    flags,
        vm_map_t               src_task,
        mach_vm_address_t      src_address,
        boolean_t              copy,
        vm_prot_t              *cur_protection,
        vm_prot_t              *max_protection,
        vm_inherit_t           inheritance
    );

    
kern_return_t mach_vm_deallocate(
       vm_map_t               target_task,
       mach_vm_address_t      address,
       mach_vm_size_t         size
   );
};
