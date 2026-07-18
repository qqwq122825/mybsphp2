//
//  SettingsViewController.m
//  ps
//
//  Created by a on 2025/8/5.
//
#import <notify.h>
#import <spawn.h>
#import <sys/wait.h>

#import <Foundation/Foundation.h>
#import <spawn.h>

#import <errno.h>


#include <time.h>
//#import "UIInitializer.h"
#import "SettingsViewController.h"
#import "TouchPointPickerViewController.h"
//#import "libgrabkernel2.h"
// Path for kernel cache file to determine initialization status


@interface SettingsViewController ()
@property (nonatomic, strong) NSArray<NSString *> *sectionTitles;
@property (nonatomic, strong) NSArray<NSArray<NSString *> *> *sectionItems;
@property (nonatomic, assign) BOOL isInitializing;
@property (nonatomic, strong) NSMutableDictionary *userDefaults;   // 自定义缓存
@end

@implementation SettingsViewController

- (instancetype)init {
    if (@available(iOS 13.0, *)) {
        self = [super initWithStyle:UITableViewStyleInsetGrouped];
    } else {
        self = [super initWithStyle:UITableViewStyleGrouped];
    }
    if (self) {
        _sectionTitles = @[@"设备状态", @"人物绘制", @"物资绘制", @"自瞄设置", @"压枪设置", @"其他设置"];
        _sectionItems = @[
            @[@"设备特征", @"初始化状态", @"模式"],
            @[@"人物数量", @"人物射线", @"人物信息", @"人物距离", @"人物骨骼", @"人物方框",@"人机显示",@"雷达显示",@"调试开关"],
            @[@"物资开关",@"盒子开关"],
            @[@"自瞄开关", @"自瞄距离", @"自瞄范围", @"自瞄模式", @"锁定模式", @"自瞄速度", @"瞄准部位", @"自瞄触摸点"],
            @[@"压枪开关", @"压枪强度", @"压枪延迟帧数"],
            @[@"直播模式", @"偏框调整"]
        ];
    }
    return self;
}
- (void)viewWillAppear:(BOOL)animated {
    [super viewWillAppear:animated];
    [self.navigationController setNavigationBarHidden:NO animated:animated];
}

- (void)viewDidLoad {
    [super viewDidLoad];
    [self loadUserDefaults:NO];   // 载入自定义缓存
    if (!self.userDefaults[@"偏框调整"]) {
        self.userDefaults[@"偏框调整"] = @(1.0);
    }
    // 设置自瞄默认值
    if (!self.userDefaults[@"锁定模式"]) self.userDefaults[@"锁定模式"] = @0; // 默认准星优先
    if (!self.userDefaults[@"自瞄速度"]) self.userDefaults[@"自瞄速度"] = @0.5; // 默认0.5
    if (!self.userDefaults[@"瞄准部位"]) self.userDefaults[@"瞄准部位"] = @1; // 默认胸部
    if (!self.userDefaults[@"自瞄距离"]) self.userDefaults[@"自瞄距离"] = @100; // 默认100米
    if (!self.userDefaults[@"自瞄范围"]) self.userDefaults[@"自瞄范围"] = @50; // 默认50像素
    if (!self.userDefaults[@"自瞄模式"]) self.userDefaults[@"自瞄模式"] = @0; // 默认开火模式
    
    // 设置压枪默认值
    if (!self.userDefaults[@"压枪强度"]) self.userDefaults[@"压枪强度"] = @2.0; // 默认2.0倍
    if (!self.userDefaults[@"压枪延迟帧数"]) self.userDefaults[@"压枪延迟帧数"] = @3; // 默认3帧后开始压枪
    self.title = @"设置";
    [self.tableView registerClass:[UITableViewCell class] forCellReuseIdentifier:@"SettingsCell"];
    // Improve margins and background
    if (@available(iOS 13.0, *)) {
        self.tableView.backgroundColor = [UIColor systemGroupedBackgroundColor];
        // `sectionHeaderTopPadding` is only available starting iOS 15.
        if (@available(iOS 15.0, *)) {
            self.tableView.sectionHeaderTopPadding = 16;
        }
    } else {
        self.tableView.backgroundColor = [UIColor groupTableViewBackgroundColor];
    }
    self.tableView.separatorInset = UIEdgeInsetsMake(0, 16, 0, 16);
    self.tableView.layoutMargins = UIEdgeInsetsMake(0, 16, 0, 16);
}

#pragma mark - UITableViewDataSource

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView {
    return self.sectionTitles.count;
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section {
    if (section == 0) {
        // for section 0: 2 switches + 1 segment
        return self.sectionItems[0].count;
    }
    return self.sectionItems[section].count;
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath {
    if (indexPath.section == 0) {
        if (indexPath.row == 0) {
            // Device serial number cell
            UITableViewCell *cell = [tableView dequeueReusableCellWithIdentifier:@"SerialCell"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleValue1 reuseIdentifier:@"SerialCell"];
            }
            // 清理 accessoryView 和自定义控件
            cell.accessoryView = nil;
            for (UIView *sub in cell.contentView.subviews) {
                if (sub.tag >= 200) [sub removeFromSuperview];
            }
            NSString *key = self.sectionItems[0][indexPath.row];
            cell.textLabel.text = key;
//            NSString *serial = [UIInitializer serialNumber];
            NSString *serial = @"123";
            cell.detailTextLabel.text = serial;
            cell.selectionStyle = UITableViewCellSelectionStyleNone;
            return cell;
        } else if (indexPath.row == 1) {
            // 初始化状态行（右侧文本）
            UITableViewCell *cell = [tableView dequeueReusableCellWithIdentifier:@"SettingsCell" forIndexPath:indexPath];
            // 清理 accessoryView 和自定义控件
            cell.accessoryView = nil;
            for (UIView *sub in cell.contentView.subviews) {
                if (sub.tag >= 200) [sub removeFromSuperview];
            }
            cell.textLabel.text = @"初始化状态";
            BOOL initialized = true;
            UILabel *statusLabel = [cell.contentView viewWithTag:401];
            if (!statusLabel) {
                statusLabel = [[UILabel alloc] init];
                statusLabel.tag = 401;
                statusLabel.translatesAutoresizingMaskIntoConstraints = NO;
                [cell.contentView addSubview:statusLabel];
                [NSLayoutConstraint activateConstraints:@[
                    [statusLabel.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [statusLabel.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16]
                ]];
            }
            statusLabel.text = initialized ? @"已初始化" : @"初始化";
            statusLabel.textColor = initialized ? [UIColor lightGrayColor] : [UIColor systemBlueColor];
            cell.selectionStyle = initialized ? UITableViewCellSelectionStyleNone : UITableViewCellSelectionStyleDefault;
            return cell;
        } else if (indexPath.row == 2) {
            // 内存模式行，使用 SegmentCell
            UITableViewCell *cell = [tableView dequeueReusableCellWithIdentifier:@"SegmentCell"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"SegmentCell"];
                UISegmentedControl *seg = [[UISegmentedControl alloc] initWithItems:@[@"模式一", @"模式二"]];
                seg.tag = 200;
                seg.translatesAutoresizingMaskIntoConstraints = NO;
                [seg addTarget:self action:@selector(modeSegmentChanged:) forControlEvents:UIControlEventValueChanged];
                [cell.contentView addSubview:seg];
                [NSLayoutConstraint activateConstraints:@[
                    [seg.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [seg.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                    [seg.widthAnchor constraintEqualToConstant:220]
                ]];
            }
            // 清理 accessoryView 和自定义控件
            cell.accessoryView = nil;
            for (UIView *sub in cell.contentView.subviews) {
                if (sub.tag >= 200 && sub.tag != 200) [sub removeFromSuperview];
            }
            cell.textLabel.text = @"模式";
            UISegmentedControl *seg = [cell.contentView viewWithTag:200];
            NSInteger current = [self.userDefaults[@"SelectedMode"] integerValue];
            seg.selectedSegmentIndex = current;
            cell.selectionStyle = UITableViewCellSelectionStyleNone;
            return cell;
        }
    }
    
    // Section 3: 自瞄设置
    if (indexPath.section == 3) {
        NSString *key = self.sectionItems[3][indexPath.row];
        UITableViewCell *cell = nil;

        if (indexPath.row == 0) {
            // 自瞄开关
            cell = [tableView dequeueReusableCellWithIdentifier:@"AimbotSwitch"];
            if (!cell) cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"AimbotSwitch"];
            cell.textLabel.text = key;
            UISwitch *sw = [[UISwitch alloc] init];
            sw.on = [self.userDefaults[key] boolValue];
            [sw addTarget:self action:@selector(switchChanged:) forControlEvents:UIControlEventValueChanged];
            cell.accessoryView = sw;
        } else if (indexPath.row == 1 || indexPath.row == 2) {
            // 自瞄距离和自瞄范围
            cell = [tableView dequeueReusableCellWithIdentifier:@"AimbotSlider"];
            if (!cell) cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"AimbotSlider"];
            BOOL isDistance = indexPath.row == 1;
            cell.textLabel.text = key;
            UIView *wrap = [[UIView alloc] initWithFrame:CGRectMake(0, 0, 260, 30)];
            UISlider *slider = [[UISlider alloc] initWithFrame:CGRectMake(0, 0, 160, 30)];
            slider.minimumValue = 0;
            slider.maximumValue = isDistance ? 300 : 200;
            slider.value = [self.userDefaults[key] floatValue];
            slider.tag = isDistance ? 700 : 701;
            [slider addTarget:self action:@selector(aimbotSliderChanged:) forControlEvents:UIControlEventValueChanged];
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(170, 0, 80, 30)];
            label.textAlignment = NSTextAlignmentRight;
            label.font = [UIFont systemFontOfSize:14];
            label.tag = isDistance ? 801 : 802;
            label.text = [NSString stringWithFormat:@"%.0f", slider.value];
            [wrap addSubview:slider];
            [wrap addSubview:label];
            cell.accessoryView = wrap;
        } else if (indexPath.row == 3) {
            // 自瞄模式
            cell = [tableView dequeueReusableCellWithIdentifier:@"AimbotModeSegment"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"AimbotModeSegment"];
                UISegmentedControl *seg = [[UISegmentedControl alloc] initWithItems:@[@"开火", @"开镜", @"镜火全开"]];
                seg.tag = 900;
                seg.translatesAutoresizingMaskIntoConstraints = NO;
                [seg addTarget:self action:@selector(aimbotModeChanged:) forControlEvents:UIControlEventValueChanged];
                [cell.contentView addSubview:seg];
                [NSLayoutConstraint activateConstraints:@[
                    [seg.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [seg.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                    [seg.widthAnchor constraintEqualToConstant:220]
                ]];
            }
            cell.textLabel.text = key;
            UISegmentedControl *seg = [cell.contentView viewWithTag:900];
            seg.selectedSegmentIndex = [self.userDefaults[key] integerValue];
        } else if (indexPath.row == 4) {
            // 锁定模式
            cell = [tableView dequeueReusableCellWithIdentifier:@"LockModeSegment"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"LockModeSegment"];
                UISegmentedControl *seg = [[UISegmentedControl alloc] initWithItems:@[@"准星优先", @"距离优先"]];
                seg.tag = 910;
                seg.translatesAutoresizingMaskIntoConstraints = NO;
                [seg addTarget:self action:@selector(lockModeChanged:) forControlEvents:UIControlEventValueChanged];
                [cell.contentView addSubview:seg];
                [NSLayoutConstraint activateConstraints:@[
                    [seg.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [seg.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                    [seg.widthAnchor constraintEqualToConstant:220]
                ]];
            }
            cell.textLabel.text = key;
            UISegmentedControl *seg = [cell.contentView viewWithTag:910];
            seg.selectedSegmentIndex = [self.userDefaults[key] integerValue];
        } else if (indexPath.row == 5) {
            // 自瞄速度
            cell = [tableView dequeueReusableCellWithIdentifier:@"AimSpeedSlider"];
            if (!cell) cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"AimSpeedSlider"];
            cell.textLabel.text = key;
            UIView *wrap = [[UIView alloc] initWithFrame:CGRectMake(0, 0, 260, 30)];
            UISlider *slider = [[UISlider alloc] initWithFrame:CGRectMake(0, 0, 160, 30)];
            slider.minimumValue = 0;
            slider.maximumValue = 1;
            slider.value = [self.userDefaults[key] floatValue];
            slider.tag = 720;
            [slider addTarget:self action:@selector(aimSpeedSliderChanged:) forControlEvents:UIControlEventValueChanged];
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(170, 0, 80, 30)];
            label.textAlignment = NSTextAlignmentRight;
            label.font = [UIFont systemFontOfSize:14];
            label.tag = 820;
            label.text = [NSString stringWithFormat:@"%.2f", slider.value];
            [wrap addSubview:slider];
            [wrap addSubview:label];
            cell.accessoryView = wrap;
        } else if (indexPath.row == 6) {
            // 瞄准部位
            cell = [tableView dequeueReusableCellWithIdentifier:@"AimPartSegment"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"AimPartSegment"];
                UISegmentedControl *seg = [[UISegmentedControl alloc] initWithItems:@[@"脖", @"胸", @"腿",@"居中"]];
                seg.tag = 920;
                seg.translatesAutoresizingMaskIntoConstraints = NO;
                [seg addTarget:self action:@selector(aimPartChanged:) forControlEvents:UIControlEventValueChanged];
                [cell.contentView addSubview:seg];
                [NSLayoutConstraint activateConstraints:@[
                    [seg.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [seg.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                    [seg.widthAnchor constraintEqualToConstant:220]
                ]];
            }
            cell.textLabel.text = key;
            UISegmentedControl *seg = [cell.contentView viewWithTag:920];
            seg.selectedSegmentIndex = [self.userDefaults[key] integerValue];
        } else if (indexPath.row == 7) {
            // 自瞄触摸点
            cell = [tableView dequeueReusableCellWithIdentifier:@"TouchPointCell"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleValue1 reuseIdentifier:@"TouchPointCell"];
            }
            cell.textLabel.text = key;
            CGPoint point = CGPointFromString(self.userDefaults[key] ?: NSStringFromCGPoint(CGPointMake(0, 0)));
            cell.detailTextLabel.text = [NSString stringWithFormat:@"%.0f, %.0f", point.x, point.y];
            cell.accessoryType = UITableViewCellAccessoryDisclosureIndicator;
            cell.selectionStyle = UITableViewCellSelectionStyleDefault;
        }
        return cell;
    }
    
    // Section 4: 压枪设置
    if (indexPath.section == 4) {
        NSString *key = self.sectionItems[4][indexPath.row];
        UITableViewCell *cell = nil;
        
        if (indexPath.row == 0) {
            // 压枪开关
            cell = [tableView dequeueReusableCellWithIdentifier:@"RecoilSwitch"];
            if (!cell) cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"RecoilSwitch"];
            cell.textLabel.text = key;
            UISwitch *sw = [[UISwitch alloc] init];
            sw.on = [self.userDefaults[key] boolValue];
            [sw addTarget:self action:@selector(switchChanged:) forControlEvents:UIControlEventValueChanged];
            cell.accessoryView = sw;
        } else if (indexPath.row == 1) {
            // 压枪强度
            cell = [tableView dequeueReusableCellWithIdentifier:@"RecoilStrengthSlider"];
            if (!cell) cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"RecoilStrengthSlider"];
            cell.textLabel.text = key;
            UIView *wrap = [[UIView alloc] initWithFrame:CGRectMake(0, 0, 260, 30)];
            UISlider *slider = [[UISlider alloc] initWithFrame:CGRectMake(0, 0, 160, 30)];
            slider.minimumValue = 0;
            slider.maximumValue = 10;
            slider.value = [self.userDefaults[key] floatValue];
            slider.tag = 730;
            [slider addTarget:self action:@selector(recoilStrengthSliderChanged:) forControlEvents:UIControlEventValueChanged];
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(170, 0, 80, 30)];
            label.textAlignment = NSTextAlignmentRight;
            label.font = [UIFont systemFontOfSize:14];
            label.tag = 830;
            label.text = [NSString stringWithFormat:@"%.1f", slider.value];
            [wrap addSubview:slider];
            [wrap addSubview:label];
            cell.accessoryView = wrap;
        } else if (indexPath.row == 2) {
            // 压枪延迟帧数
            cell = [tableView dequeueReusableCellWithIdentifier:@"RecoilDelayCell"];
            if (!cell) {
                cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"RecoilDelayCell"];
                cell.selectionStyle = UITableViewCellSelectionStyleNone;
                
                UILabel *label = [[UILabel alloc] init];
                label.tag = 503;
                label.font = [UIFont systemFontOfSize:14];
                label.textColor = [UIColor secondaryLabelColor];
                label.translatesAutoresizingMaskIntoConstraints = NO;
                
                UIStepper *stepper = [[UIStepper alloc] init];
                stepper.tag = 504;
                stepper.minimumValue = 0;
                stepper.maximumValue = 10;
                stepper.stepValue = 1;
                stepper.translatesAutoresizingMaskIntoConstraints = NO;
                [stepper addTarget:self action:@selector(recoilDelayStepper:) forControlEvents:UIControlEventValueChanged];
                
                [cell.contentView addSubview:label];
                [cell.contentView addSubview:stepper];
                
                [NSLayoutConstraint activateConstraints:@[
                    [stepper.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [stepper.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                    [label.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                    [label.rightAnchor constraintEqualToAnchor:stepper.leftAnchor constant:-8],
                ]];
            }
            
            cell.textLabel.text = key;
            int value = [self.userDefaults[key] intValue] ?: 3;
            UILabel *label = [cell.contentView viewWithTag:503];
            UIStepper *stepper = [cell.contentView viewWithTag:504];
            label.text = [NSString stringWithFormat:@"%d 帧", value];
            stepper.value = value;
        }
        return cell;
    }
    
    NSString *key = self.sectionItems[indexPath.section][indexPath.row];
    UITableViewCell *cell = nil;
  
    if (indexPath.section == 2 ) {          // 物资开关
        cell = [tableView dequeueReusableCellWithIdentifier:@"SwitchCell"];
        if (!cell) {
            cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"SwitchCell"];
        }
        // 清理 accessoryView 和自定义控件
        cell.accessoryView = nil;
        for (UIView *sub in cell.contentView.subviews) {
            if (sub.tag >= 200) [sub removeFromSuperview];
        }
        cell.textLabel.text = key;
        UISwitch *sw = [[UISwitch alloc] init];
        sw.on = [self.userDefaults[key] boolValue];
        [sw addTarget:self action:@selector(switchChanged:) forControlEvents:UIControlEventValueChanged];
        cell.accessoryView = sw;
    }  else if (indexPath.section == 5 && indexPath.row == 1) {
        cell = [tableView dequeueReusableCellWithIdentifier:@"OffsetAdjustCell"];
        if (!cell) {
            cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"OffsetAdjustCell"];
            cell.selectionStyle = UITableViewCellSelectionStyleNone;

            UILabel *label = [[UILabel alloc] init];
            label.tag = 501;
            label.font = [UIFont systemFontOfSize:14];
            label.textColor = [UIColor secondaryLabelColor];
            label.translatesAutoresizingMaskIntoConstraints = NO;

            UIStepper *stepper = [[UIStepper alloc] init];
            stepper.tag = 502;
            stepper.minimumValue = 0.5;
            stepper.maximumValue = 1.5;
            stepper.stepValue = 0.01;
            stepper.translatesAutoresizingMaskIntoConstraints = NO;
            [stepper addTarget:self action:@selector(offsetStepperChanged:) forControlEvents:UIControlEventValueChanged];

            [cell.contentView addSubview:label];
            [cell.contentView addSubview:stepper];

            [NSLayoutConstraint activateConstraints:@[
                [stepper.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                [stepper.rightAnchor constraintEqualToAnchor:cell.contentView.rightAnchor constant:-16],
                [label.centerYAnchor constraintEqualToAnchor:cell.contentView.centerYAnchor],
                [label.rightAnchor constraintEqualToAnchor:stepper.leftAnchor constant:-8],
            ]];
        }

        cell.textLabel.text = key;
        double value = [self.userDefaults[key] doubleValue] ?: 1.0;
        UILabel *label = [cell.contentView viewWithTag:501];
        UIStepper *stepper = [cell.contentView viewWithTag:502];
        label.text = [NSString stringWithFormat:@"%.2f", value];
        stepper.value = value;
        return cell;
    } else {                                          // default switch
        cell = [tableView dequeueReusableCellWithIdentifier:@"DefaultSwitchCell"];
        if (!cell) {
            cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:@"DefaultSwitchCell"];
        }
        // 清理 accessoryView 和自定义控件
        cell.accessoryView = nil;
        for (UIView *sub in cell.contentView.subviews) {
            if (sub.tag >= 200) [sub removeFromSuperview];
        }
        cell.textLabel.text = key;
        UISwitch *sw = [[UISwitch alloc] init];
        sw.on = [self.userDefaults[key] boolValue];
        [sw addTarget:self action:@selector(switchChanged:) forControlEvents:UIControlEventValueChanged];
        cell.accessoryView = sw;
    }
    return cell;
}

#pragma mark - UITableViewDelegate

- (void)tableView:(UITableView *)tableView willDisplayCell:(UITableViewCell *)cell forRowAtIndexPath:(NSIndexPath *)indexPath {
    cell.separatorInset = UIEdgeInsetsMake(0, 16, 0, 16);
    cell.layoutMargins = UIEdgeInsetsMake(0, 16, 0, 16);
}

- (UIView *)tableView:(UITableView *)tableView viewForHeaderInSection:(NSInteger)section {
    UIView *header = [[UIView alloc] init];
    header.backgroundColor = tableView.backgroundColor;
    UIImageView *icon = [[UIImageView alloc] init];
    icon.translatesAutoresizingMaskIntoConstraints = NO;
    NSString *imageName;
    if (section == 0) {
        imageName = @"gearshape.fill";
    } else if (section == 1) {
        imageName = @"person.fill";
    } else {
        imageName = @"cart.fill";
    }
    if (@available(iOS 13.0, *)) {
        icon.image = [UIImage systemImageNamed:imageName];
    }
    icon.tintColor = [UIColor systemBlueColor];
    [header addSubview:icon];
    UILabel *titleLabel = [[UILabel alloc] init];
    titleLabel.translatesAutoresizingMaskIntoConstraints = NO;
    titleLabel.font = [UIFont systemFontOfSize:17 weight:UIFontWeightBold];
    titleLabel.text = self.sectionTitles[section];
    [header addSubview:titleLabel];
    [NSLayoutConstraint activateConstraints:@[
        [icon.leftAnchor constraintEqualToAnchor:header.leftAnchor constant:16],
        [icon.centerYAnchor constraintEqualToAnchor:header.centerYAnchor],
        [icon.widthAnchor constraintEqualToConstant:20],
        [icon.heightAnchor constraintEqualToConstant:20],
        [titleLabel.leftAnchor constraintEqualToAnchor:icon.rightAnchor constant:8],
        [titleLabel.centerYAnchor constraintEqualToAnchor:header.centerYAnchor],
    ]];
    return header;
}

- (CGFloat)tableView:(UITableView *)tableView heightForHeaderInSection:(NSInteger)section {
    return 40;
}

#pragma mark - Switch Action

- (void)switchChanged:(UISwitch *)sender {
    // Find the indexPath of this switch
    CGPoint pt = [sender convertPoint:CGPointZero toView:self.tableView];
    NSIndexPath *indexPath = [self.tableView indexPathForRowAtPoint:pt];
    if (!indexPath) return;
    NSString *key = self.sectionItems[indexPath.section][indexPath.row];
    [self updateSettingForKey:key value:@(sender.on)];
    // Notify HUD to reload
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)levelSliderChanged:(UISlider *)sender {
    sender.value = roundf(sender.value); // 步长为1
    [self updateSettingForKey:@"物资等级" value:@((int)sender.value)];
    
    UILabel *label = [sender.superview viewWithTag:501];
    if (label) label.text = [NSString stringWithFormat:@"%d", (int)sender.value];
    
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)valueSliderChanged:(UISlider *)sender {
    [self updateSettingForKey:@"物资价值" value:@(sender.value)];
    
    UILabel *label = [sender.superview viewWithTag:502];
    if (label) label.text = [NSString stringWithFormat:@"%.0f", sender.value];
    
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)modeSegmentChanged:(UISegmentedControl *)seg {
    NSInteger idx = seg.selectedSegmentIndex;
    [self updateSettingForKey:@"SelectedMode" value:@(idx)];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)offsetStepperChanged:(UIStepper *)sender {
    CGPoint pt = [sender convertPoint:CGPointZero toView:self.tableView];
    NSIndexPath *indexPath = [self.tableView indexPathForRowAtPoint:pt];
    if (!indexPath) return;
    NSString *key = self.sectionItems[indexPath.section][indexPath.row];
    [self updateSettingForKey:key value:@(sender.value)];

    UITableViewCell *cell = [self.tableView cellForRowAtIndexPath:indexPath];
    UILabel *label = [cell.contentView viewWithTag:501];
    if (label) label.text = [NSString stringWithFormat:@"%.2f", sender.value];

    notify_post(NOTIFY_RELOAD_HUD);
}

#pragma mark - UITableViewDelegate (row selection)

// 行点击
- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath {
    if (indexPath.section == 0 && indexPath.row == 1) {

    }
    
    // 自瞄触摸点选择
    if (indexPath.section == 3 && indexPath.row == 7) {
        TouchPointPickerViewController *vc = [[TouchPointPickerViewController alloc] init];
        vc.title = @"选择自瞄触摸点";
        __weak typeof(self) weakSelf = self;
        vc.onTouchPointSelected = ^(CGPoint point) {
            [weakSelf updateSettingForKey:@"自瞄触摸点" value:NSStringFromCGPoint(point)];
            [weakSelf.tableView reloadRowsAtIndexPaths:@[indexPath] withRowAnimation:UITableViewRowAnimationNone];
        };
        [self.navigationController pushViewController:vc animated:YES];
    }
    
    [tableView deselectRowAtIndexPath:indexPath animated:YES];
}

#pragma mark - 初始化逻辑


#pragma mark - Custom UserDefaults helpers
- (void)loadUserDefaults:(BOOL)forceReload {
    if (forceReload || !self.userDefaults) {
        NSString *path = USER_DEFAULTS_PATH;
        self.userDefaults =
            [[NSDictionary dictionaryWithContentsOfFile:path] mutableCopy] ?: [NSMutableDictionary dictionary];
    }
}

- (void)saveUserDefaults {
    NSString *path = USER_DEFAULTS_PATH;
    [self.userDefaults writeToFile:path atomically:YES];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)updateSettingForKey:(NSString *)key value:(id)value {
    if (key && value) {
        self.userDefaults[key] = value;
        [self saveUserDefaults];
    }
}

- (void)removeSettingForKey:(NSString *)key {
    if (self.userDefaults[key]) {
        [self.userDefaults removeObjectForKey:key];
        [self saveUserDefaults];
    }
}

#pragma mark - 自瞄设置支持

- (void)aimbotSliderChanged:(UISlider *)sender {
    NSString *key = sender.tag == 700 ? @"自瞄距离" : @"自瞄范围";
    [self updateSettingForKey:key value:@(sender.value)];
    UILabel *label = [sender.superview viewWithTag:(sender.tag == 700 ? 801 : 802)];
    if (label) label.text = [NSString stringWithFormat:@"%.0f", sender.value];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)aimSpeedSliderChanged:(UISlider *)sender {
    [self updateSettingForKey:@"自瞄速度" value:@(sender.value)];
    UILabel *label = [sender.superview viewWithTag:820];
    if (label) label.text = [NSString stringWithFormat:@"%.2f", sender.value];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)aimbotModeChanged:(UISegmentedControl *)seg {
    [self updateSettingForKey:@"自瞄模式" value:@(seg.selectedSegmentIndex)];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)lockModeChanged:(UISegmentedControl *)seg {
    [self updateSettingForKey:@"锁定模式" value:@(seg.selectedSegmentIndex)];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)aimPartChanged:(UISegmentedControl *)seg {
    [self updateSettingForKey:@"瞄准部位" value:@(seg.selectedSegmentIndex)];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)recoilStrengthSliderChanged:(UISlider *)sender {
    [self updateSettingForKey:@"压枪强度" value:@(sender.value)];
    UILabel *label = [sender.superview viewWithTag:830];
    if (label) label.text = [NSString stringWithFormat:@"%.1f", sender.value];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)recoilDelayStepper:(UIStepper *)sender {
    [self updateSettingForKey:@"压枪延迟帧数" value:@((int)sender.value)];
    UILabel *label = (UILabel *)[sender.superview viewWithTag:503];
    if (label) label.text = [NSString stringWithFormat:@"%d 帧", (int)sender.value];
    notify_post(NOTIFY_RELOAD_HUD);
}

@end
