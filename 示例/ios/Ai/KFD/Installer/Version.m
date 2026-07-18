#import "Version.h"

@implementation Version

- (instancetype)initWithString:(NSString *)versionString {
    self = [super init];
    if (self) {
        NSArray<NSString *> *components = [versionString componentsSeparatedByString:@"."];
        
        _major = components.count > 0 ? [components[0] integerValue] : 0;
        _minor = components.count > 1 ? [components[1] integerValue] : 0;
        
        _patch = components.count > 2 ? @([components[2] integerValue]) : nil;
    }
    return self;
}

- (NSComparisonResult)compare:(Version *)otherVersion {
    if (self.major != otherVersion.major) {
        return (self.major < otherVersion.major) ? NSOrderedAscending : NSOrderedDescending;
    }
    
    if (self.minor != otherVersion.minor) {
        return (self.minor < otherVersion.minor) ? NSOrderedAscending : NSOrderedDescending;
    }
    
    if (self.patch && otherVersion.patch) {
        if ([self.patch integerValue] != [otherVersion.patch integerValue]) {
            return ([self.patch integerValue] < [otherVersion.patch integerValue]) ? NSOrderedAscending : NSOrderedDescending;
        }
    } else if (self.patch) {
        return NSOrderedDescending;
    } else if (otherVersion.patch) {
        return NSOrderedAscending;
    }
    
    return NSOrderedSame;
}

- (BOOL)isLessThan:(Version *)otherVersion {
    return [self compare:otherVersion] == NSOrderedAscending;
}

- (BOOL)isLessThanOrEqualTo:(Version *)otherVersion {
    NSComparisonResult result = [self compare:otherVersion];
    return result == NSOrderedAscending || result == NSOrderedSame;
}

- (BOOL)isEqualToVersion:(Version *)otherVersion {
    return [self compare:otherVersion] == NSOrderedSame;
}

- (NSString *)readableString {
    return self.patch ? [NSString stringWithFormat:@"%ld.%ld.%ld",
                        (long)self.major,
                        (long)self.minor,
                        (long)[self.patch integerValue]]
                     : [NSString stringWithFormat:@"%ld.%ld",
                        (long)self.major,
                        (long)self.minor];
}

#pragma mark - NSObject

- (BOOL)isEqual:(id)object {
    if (self == object) return YES;
    if (![object isKindOfClass:[Version class]]) return NO;
    return [self isEqualToVersion:(Version *)object];
}

- (NSUInteger)hash {
    return self.major ^ self.minor ^ [self.patch integerValue];
}

@end
