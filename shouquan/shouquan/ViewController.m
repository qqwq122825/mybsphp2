#import "ViewController.h"
#import "BSPHPConfig.h"
#import "BSPHPHeartbeatManager.h"
#import "BSPHPResponseProcessor.h"
#import "NetTool.h"

typedef NS_ENUM(NSInteger, BSDemoMode) {
    BSDemoModeCard,
    BSDemoModeAccount,
};

@interface ViewController ()
@property (nonatomic) BSDemoMode mode;
@property (nonatomic, strong) UIButton *cardModeButton;
@property (nonatomic, strong) UIButton *accountModeButton;
@property (nonatomic, strong) UIStackView *cardForm;
@property (nonatomic, strong) UIStackView *accountForm;
@property (nonatomic, strong) UITextField *cardField;
@property (nonatomic, strong) UITextField *usernameField;
@property (nonatomic, strong) UITextField *passwordField;
@property (nonatomic, strong) UITextField *inviteField;
@property (nonatomic, strong) UITextField *rechargeCardField;
@property (nonatomic, strong) UIButton *cardSubmitButton;
@property (nonatomic, strong) UIButton *registerButton;
@property (nonatomic, strong) UIButton *loginButton;
@property (nonatomic, strong) UIButton *rechargeButton;
@property (nonatomic, strong) UIView *resultCard;
@property (nonatomic, strong) UILabel *resultLabel;
@property (nonatomic, copy) NSString *sessionHandle;
@end

@implementation ViewController

- (void)viewDidLoad {
    [super viewDidLoad];
    self.view.backgroundColor = [UIColor colorWithRed:0.95 green:0.97 blue:1 alpha:1];
    self.sessionHandle = [BSPHPHeartbeatManager newSessionHandle];
    [[NSNotificationCenter defaultCenter] addObserver:self
                                             selector:@selector(heartbeatStateDidChange:)
                                                 name:BSPHPHeartbeatStateDidChangeNotification
                                               object:BSPHPHeartbeatManager.sharedManager];
    [self buildInterface];
    [self selectMode:BSDemoModeCard];
}

- (void)buildInterface {
    UIScrollView *scroll = [UIScrollView new];
    scroll.translatesAutoresizingMaskIntoConstraints = NO;
    scroll.keyboardDismissMode = UIScrollViewKeyboardDismissModeInteractive;
    [self.view addSubview:scroll];

    UIStackView *content = [UIStackView new];
    content.translatesAutoresizingMaskIntoConstraints = NO;
    content.axis = UILayoutConstraintAxisVertical;
    content.spacing = 18;
    [scroll addSubview:content];

    UILabel *badge = [UILabel new];
    badge.text = @"BSPHP iOS DEMO";
    badge.textColor = [UIColor colorWithRed:0.20 green:0.40 blue:0.91 alpha:1];
    badge.font = [UIFont systemFontOfSize:12 weight:UIFontWeightBold];

    UILabel *title = [UILabel new];
    title.text = @"授权接入示例";
    title.textColor = [UIColor colorWithRed:0.08 green:0.12 blue:0.23 alpha:1];
    title.font = [UIFont systemFontOfSize:30 weight:UIFontWeightBold];

    UILabel *subtitle = [UILabel new];
    subtitle.text = @"卡串验证和账户密码验证使用相同的加密网络层。";
    subtitle.numberOfLines = 0;
    subtitle.textColor = [UIColor colorWithWhite:0.43 alpha:1];
    subtitle.font = [UIFont systemFontOfSize:14];

    self.cardModeButton = [self modeButton:@"卡串验证" action:@selector(showCardMode)];
    self.accountModeButton = [self modeButton:@"账户密码验证" action:@selector(showAccountMode)];
    UIStackView *modeButtons = [[UIStackView alloc] initWithArrangedSubviews:@[self.cardModeButton, self.accountModeButton]];
    modeButtons.axis = UILayoutConstraintAxisHorizontal;
    modeButtons.distribution = UIStackViewDistributionFillEqually;
    modeButtons.spacing = 10;
    [modeButtons.heightAnchor constraintEqualToConstant:48].active = YES;

    self.cardForm = [self formContainer];
    UILabel *cardTitle = [self sectionTitle:@"卡串验证"];
    self.cardField = [self textField:@"输入充值卡号" secure:NO];
    self.cardSubmitButton = [self primaryButton:@"验证卡串" action:@selector(activateCard)];
    [self.cardForm addArrangedSubview:cardTitle];
    [self.cardForm addArrangedSubview:[self fieldBlock:@"充值卡号" field:self.cardField]];
    [self.cardForm addArrangedSubview:self.cardSubmitButton];

    self.accountForm = [self formContainer];
    UILabel *accountTitle = [self sectionTitle:@"账户注册与登录"];
    self.usernameField = [self textField:@"字母开头，3-32 位" secure:NO];
    self.passwordField = [self textField:@"密码长度 6-72 位" secure:YES];
    self.inviteField = [self textField:@"可选，注册时填写" secure:NO];
    self.rechargeCardField = [self textField:@"可选，只填写充值卡号" secure:NO];
    self.registerButton = [self primaryButton:@"注册账户" action:@selector(registerAccount)];
    self.loginButton = [self secondaryButton:@"登录账户" action:@selector(loginAccount)];
    self.rechargeButton = [self secondaryButton:@"登录后使用充值卡续期" action:@selector(rechargeAccount)];
    self.rechargeButton.hidden = YES;
    UIStackView *accountActions = [[UIStackView alloc] initWithArrangedSubviews:@[self.registerButton, self.loginButton]];
    accountActions.axis = UILayoutConstraintAxisHorizontal;
    accountActions.distribution = UIStackViewDistributionFillEqually;
    accountActions.spacing = 10;
    [self.accountForm addArrangedSubview:accountTitle];
    [self.accountForm addArrangedSubview:[self fieldBlock:@"用户名" field:self.usernameField]];
    [self.accountForm addArrangedSubview:[self fieldBlock:@"密码" field:self.passwordField]];
    [self.accountForm addArrangedSubview:[self fieldBlock:@"他人邀请码（注册可选）" field:self.inviteField]];
    [self.accountForm addArrangedSubview:[self fieldBlock:@"充值卡（注册可选 / 登录后续期）" field:self.rechargeCardField]];
    [self.accountForm addArrangedSubview:accountActions];
    [self.accountForm addArrangedSubview:self.rechargeButton];

    self.resultCard = [UIView new];
    self.resultCard.backgroundColor = [UIColor colorWithRed:0.94 green:0.97 blue:1 alpha:1];
    self.resultCard.layer.cornerRadius = 14;
    self.resultCard.layer.borderWidth = 1;
    self.resultCard.layer.borderColor = [UIColor colorWithRed:0.78 green:0.84 blue:0.94 alpha:1].CGColor;
    self.resultCard.hidden = YES;
    self.resultLabel = [UILabel new];
    self.resultLabel.translatesAutoresizingMaskIntoConstraints = NO;
    self.resultLabel.numberOfLines = 0;
    self.resultLabel.font = [UIFont monospacedSystemFontOfSize:12 weight:UIFontWeightRegular];
    self.resultLabel.textColor = [UIColor colorWithRed:0.10 green:0.20 blue:0.34 alpha:1];
    [self.resultCard addSubview:self.resultLabel];
    [NSLayoutConstraint activateConstraints:@[
        [self.resultLabel.topAnchor constraintEqualToAnchor:self.resultCard.topAnchor constant:16],
        [self.resultLabel.leadingAnchor constraintEqualToAnchor:self.resultCard.leadingAnchor constant:16],
        [self.resultLabel.trailingAnchor constraintEqualToAnchor:self.resultCard.trailingAnchor constant:-16],
        [self.resultLabel.bottomAnchor constraintEqualToAnchor:self.resultCard.bottomAnchor constant:-16],
    ]];

    [content addArrangedSubview:badge];
    [content addArrangedSubview:title];
    [content addArrangedSubview:subtitle];
    [content setCustomSpacing:24 afterView:subtitle];
    [content addArrangedSubview:modeButtons];
    [content addArrangedSubview:self.cardForm];
    [content addArrangedSubview:self.accountForm];
    [content addArrangedSubview:self.resultCard];

    UILayoutGuide *safe = self.view.safeAreaLayoutGuide;
    [NSLayoutConstraint activateConstraints:@[
        [scroll.topAnchor constraintEqualToAnchor:safe.topAnchor],
        [scroll.leadingAnchor constraintEqualToAnchor:self.view.leadingAnchor],
        [scroll.trailingAnchor constraintEqualToAnchor:self.view.trailingAnchor],
        [scroll.bottomAnchor constraintEqualToAnchor:self.view.bottomAnchor],
        [content.topAnchor constraintEqualToAnchor:scroll.contentLayoutGuide.topAnchor constant:28],
        [content.leadingAnchor constraintEqualToAnchor:scroll.contentLayoutGuide.leadingAnchor constant:20],
        [content.trailingAnchor constraintEqualToAnchor:scroll.contentLayoutGuide.trailingAnchor constant:-20],
        [content.bottomAnchor constraintEqualToAnchor:scroll.contentLayoutGuide.bottomAnchor constant:-32],
        [content.widthAnchor constraintEqualToAnchor:scroll.frameLayoutGuide.widthAnchor constant:-40],
    ]];
}

- (UIStackView *)formContainer {
    UIStackView *stack = [UIStackView new];
    stack.axis = UILayoutConstraintAxisVertical;
    stack.spacing = 15;
    stack.layoutMargins = UIEdgeInsetsMake(22, 18, 22, 18);
    stack.layoutMarginsRelativeArrangement = YES;
    stack.backgroundColor = UIColor.whiteColor;
    stack.layer.cornerRadius = 18;
    stack.layer.borderWidth = 1;
    stack.layer.borderColor = [UIColor colorWithRed:0.86 green:0.89 blue:0.94 alpha:1].CGColor;
    return stack;
}

- (UIView *)fieldBlock:(NSString *)title field:(UITextField *)field {
    UILabel *label = [UILabel new];
    label.text = title;
    label.font = [UIFont systemFontOfSize:13 weight:UIFontWeightSemibold];
    label.textColor = [UIColor colorWithRed:0.16 green:0.20 blue:0.30 alpha:1];
    UIStackView *stack = [[UIStackView alloc] initWithArrangedSubviews:@[label, field]];
    stack.axis = UILayoutConstraintAxisVertical;
    stack.spacing = 7;
    return stack;
}

- (UITextField *)textField:(NSString *)placeholder secure:(BOOL)secure {
    UITextField *field = [UITextField new];
    field.placeholder = placeholder;
    field.secureTextEntry = secure;
    field.autocapitalizationType = UITextAutocapitalizationTypeNone;
    field.autocorrectionType = UITextAutocorrectionTypeNo;
    field.clearButtonMode = UITextFieldViewModeWhileEditing;
    field.font = [UIFont systemFontOfSize:15];
    field.layer.cornerRadius = 11;
    field.layer.borderWidth = 1;
    field.layer.borderColor = [UIColor colorWithRed:0.82 green:0.86 blue:0.93 alpha:1].CGColor;
    UIView *padding = [[UIView alloc] initWithFrame:CGRectMake(0, 0, 13, 1)];
    field.leftView = padding;
    field.leftViewMode = UITextFieldViewModeAlways;
    [field.heightAnchor constraintEqualToConstant:48].active = YES;
    return field;
}

- (UILabel *)sectionTitle:(NSString *)text {
    UILabel *label = [UILabel new];
    label.text = text;
    label.font = [UIFont systemFontOfSize:19 weight:UIFontWeightBold];
    label.textColor = [UIColor colorWithRed:0.08 green:0.12 blue:0.23 alpha:1];
    return label;
}

- (UIButton *)modeButton:(NSString *)title action:(SEL)action {
    UIButton *button = [UIButton buttonWithType:UIButtonTypeSystem];
    [button setTitle:title forState:UIControlStateNormal];
    button.titleLabel.font = [UIFont systemFontOfSize:14 weight:UIFontWeightBold];
    button.layer.cornerRadius = 11;
    button.layer.borderWidth = 1;
    [button addTarget:self action:action forControlEvents:UIControlEventTouchUpInside];
    return button;
}

- (UIButton *)primaryButton:(NSString *)title action:(SEL)action {
    UIButton *button = [UIButton buttonWithType:UIButtonTypeSystem];
    [button setTitle:title forState:UIControlStateNormal];
    [button setTitleColor:UIColor.whiteColor forState:UIControlStateNormal];
    button.titleLabel.font = [UIFont systemFontOfSize:15 weight:UIFontWeightBold];
    button.backgroundColor = [UIColor colorWithRed:0.20 green:0.40 blue:0.91 alpha:1];
    button.layer.cornerRadius = 11;
    [button.heightAnchor constraintEqualToConstant:48].active = YES;
    [button addTarget:self action:action forControlEvents:UIControlEventTouchUpInside];
    return button;
}

- (UIButton *)secondaryButton:(NSString *)title action:(SEL)action {
    UIButton *button = [self primaryButton:title action:action];
    [button setTitleColor:[UIColor colorWithRed:0.18 green:0.34 blue:0.74 alpha:1] forState:UIControlStateNormal];
    button.backgroundColor = [UIColor colorWithRed:0.91 green:0.94 blue:1 alpha:1];
    return button;
}

- (void)showCardMode { [self selectMode:BSDemoModeCard]; }
- (void)showAccountMode { [self selectMode:BSDemoModeAccount]; }

- (void)selectMode:(BSDemoMode)mode {
    self.mode = mode;
    self.cardForm.hidden = mode != BSDemoModeCard;
    self.accountForm.hidden = mode != BSDemoModeAccount;
    [self styleModeButton:self.cardModeButton selected:mode == BSDemoModeCard];
    [self styleModeButton:self.accountModeButton selected:mode == BSDemoModeAccount];
    self.resultCard.hidden = YES;
}

- (void)styleModeButton:(UIButton *)button selected:(BOOL)selected {
    button.backgroundColor = selected ? [UIColor colorWithRed:0.20 green:0.40 blue:0.91 alpha:1] : UIColor.whiteColor;
    [button setTitleColor:selected ? UIColor.whiteColor : [UIColor colorWithRed:0.25 green:0.31 blue:0.45 alpha:1] forState:UIControlStateNormal];
    button.layer.borderColor = (selected ? [UIColor colorWithRed:0.20 green:0.40 blue:0.91 alpha:1] : [UIColor colorWithRed:0.82 green:0.86 blue:0.93 alpha:1]).CGColor;
}

- (void)activateCard {
    NSArray<NSString *> *credential = [self splitCard:self.cardField.text];
    if ([credential[0] length] == 0) { [self showError:@"请输入卡号"]; return; }

    NSString *safeCode = [self requestCheck];
    NSDictionary *param = @{
        @"api": @"login.ic", @"icid": credential[0], @"icpwd": credential[1],
        @"key": [self deviceID], @"appsafecode": safeCode, @"BSphpSeSsL": self.sessionHandle,
    };
    [self setLoading:YES];
    __weak typeof(self) weakSelf = self;
    [NetTool Post_AppendURL:BSPHP_CARD_HOST parameters:param success:^(id responseObject) {
        NSError *error = nil;
        NSDictionary *data = [BSPHPResponseProcessor processCardResponse:responseObject cardCode:credential[0] appSafeCode:safeCode error:&error];
        [weakSelf setLoading:NO];
        if (error) [weakSelf showError:error.localizedDescription];
        else { [weakSelf showCardSuccess:data]; [weakSelf beginHeartbeat:BSDemoModeCard response:data]; }
    } failure:^(NSError *error) {
        [weakSelf setLoading:NO]; [weakSelf showError:error.localizedDescription];
    }];
}

- (void)registerAccount { [self submitAccountAPI:@"registration.lg"]; }
- (void)loginAccount { [self submitAccountAPI:@"login.lg"]; }
- (void)rechargeAccount { [self submitAccountAPI:@"chong.lg"]; }

- (void)submitAccountAPI:(NSString *)api {
    NSString *username = [self trimmed:self.usernameField.text];
    NSString *password = self.passwordField.text ?: @"";
    if (username.length == 0 || password.length == 0) { [self showError:@"请输入用户名和密码"]; return; }

    NSString *safeCode = [self requestCheck];
    NSMutableDictionary *param = [@{
        @"api": api, @"user": username, @"pwd": password,
        @"key": [self deviceID], @"appsafecode": safeCode,
        @"BSphpSeSsL": self.sessionHandle,
    } mutableCopy];
    if ([api isEqualToString:@"registration.lg"] || [api isEqualToString:@"chong.lg"]) {
        NSArray<NSString *> *card = [self splitCard:self.rechargeCardField.text];
        if ([api isEqualToString:@"chong.lg"] && [card[0] length] == 0) { [self showError:@"请输入充值卡"]; return; }
        param[@"icid"] = card[0];
        param[@"icpwd"] = card[1];
    }
    if ([api isEqualToString:@"registration.lg"]) {
        param[@"invite"] = [self trimmed:self.inviteField.text];
    }

    [self setLoading:YES];
    __weak typeof(self) weakSelf = self;
    [NetTool Post_AppendURL:BSPHP_ACCOUNT_HOST parameters:param success:^(id responseObject) {
        NSError *error = nil;
        NSDictionary *data = [BSPHPResponseProcessor processAccountResponse:responseObject appSafeCode:safeCode error:&error];
        [weakSelf setLoading:NO];
        if (error) [weakSelf showError:error.localizedDescription];
        else { [weakSelf showAccountSuccess:data action:api]; if (![api isEqualToString:@"chong.lg"]) [weakSelf beginHeartbeat:BSDemoModeAccount response:data]; }
    } failure:^(NSError *error) {
        [weakSelf setLoading:NO];
        if ([error.userInfo[@"BSPHPErrorCode"] isEqualToString:@"ACCOUNT_EXPIRED"]) {
            NSDictionary *details = error.userInfo[@"BSPHPErrorDetails"];
            NSDictionary *account = details[@"account"];
            weakSelf.rechargeButton.hidden = NO;
            weakSelf.resultLabel.text = [NSString stringWithFormat:@"✓ 账号密码验证成功\n\n授权状态：已过期\n用户：%@\n到期：%@\n\n请在上方输入充值卡续期。",
                account[@"username"] ?: username, [weakSelf displayDate:account[@"expires_at"]]];
            [weakSelf showResult:NO];
        } else {
            [weakSelf showError:error.localizedDescription];
        }
    }];
}

- (void)beginHeartbeat:(BSDemoMode)mode response:(NSDictionary *)response {
    NSString *returned = [response[@"BSphpSeSsL"] isKindOfClass:NSString.class] ? response[@"BSphpSeSsL"] : @"";
    if (returned.length != 64) return;
    self.sessionHandle = returned;
    NSString *api = mode == BSDemoModeAccount ? @"timeout.lg" : @"timeout.ic";
    NSString *host = mode == BSDemoModeAccount ? BSPHP_ACCOUNT_HOST : BSPHP_CARD_HOST;
    [BSPHPHeartbeatManager.sharedManager startWithAPI:api
                                                 host:host
                                        sessionHandle:returned
                                             deviceID:[self deviceID]
                                             interval:BSPHP_HEARTBEAT_INTERVAL];
    NSLog(@"[BSPHP][BSphpSeSsL] 已建立登录连接：%@", self.sessionHandle);
}

- (void)heartbeatStateDidChange:(NSNotification *)notification {
    BOOL drawingAllowed = [notification.userInfo[BSPHPHeartbeatDrawingAllowedKey] boolValue];
    if (drawingAllowed) return;
    NSString *code = notification.userInfo[BSPHPHeartbeatStatusCodeKey] ?: @"UNKNOWN";
    NSString *message = notification.userInfo[BSPHPHeartbeatMessageKey] ?: @"心跳状态异常";
    [self showError:[NSString stringWithFormat:@"心跳验证失败（%@）\n%@\n\n插件绘制权限已关闭。", code, message]];
}

- (void)dealloc {
    [[NSNotificationCenter defaultCenter] removeObserver:self];
}

- (NSArray<NSString *> *)splitCard:(NSString *)rawValue {
    NSString *raw = [self trimmed:rawValue];
    NSString *code = raw, *secret = @"";
    NSRange separator = [raw rangeOfString:@"----"];
    if (separator.location != NSNotFound) {
        code = [self trimmed:[raw substringToIndex:separator.location]];
        secret = [self trimmed:[raw substringFromIndex:NSMaxRange(separator)]];
        secret = [secret stringByTrimmingCharactersInSet:[NSCharacterSet characterSetWithCharactersInString:@"-"]];
    }
    return @[code ?: @"", secret ?: @""];
}

- (NSString *)trimmed:(NSString *)value {
    return [value ?: @"" stringByTrimmingCharactersInSet:NSCharacterSet.whitespaceAndNewlineCharacterSet];
}

- (NSString *)deviceID {
    return [NSString stringWithFormat:@"IOS-SHOUQUAN-%@", UIDevice.currentDevice.identifierForVendor.UUIDString ?: @"SIMULATOR"];
}

- (NSString *)requestCheck {
    return [NSString stringWithFormat:@"%@-%lld-%@", BSPHP_APPSAFECODE,
            (long long)(NSDate.date.timeIntervalSince1970 * 1000), NSUUID.UUID.UUIDString];
}

- (void)setLoading:(BOOL)loading {
    self.cardSubmitButton.enabled = !loading;
    self.registerButton.enabled = !loading;
    self.loginButton.enabled = !loading;
    self.rechargeButton.enabled = !loading;
    [self.view endEditing:YES];
}

- (void)showCardSuccess:(NSDictionary *)data {
    NSDictionary *card = data[@"card"];
    self.resultLabel.text = [NSString stringWithFormat:@"✓ 卡串验证成功\n\n软件：%@\n卡号：%@\n到期：%@\n设备：%@\n验证数据：%@\n每日校验：通过\n每次校验：通过",
        data[@"application"][@"name"] ?: @"—", card[@"masked"] ?: @"—",
        [self displayDate:card[@"expires_at"]], data[@"device"] ?: @"—", data[@"payload"] ?: @"—"];
    [self showResult:YES];
}

- (void)showAccountSuccess:(NSDictionary *)data action:(NSString *)api {
    NSDictionary *account = data[@"account"];
    NSString *action = [api isEqualToString:@"registration.lg"] ? @"账户注册成功" : ([api isEqualToString:@"chong.lg"] ? @"充值成功" : @"账户登录成功");
    NSString *expiry = [self displayDate:account[@"expires_at"]];
    BOOL expired = [self isExpiredDate:account[@"expires_at"]];
    self.rechargeButton.hidden = NO;
    self.resultLabel.text = [NSString stringWithFormat:@"✓ %@\n\n软件：%@\n用户：%@\n授权状态：%@\n到期：%@\n我的邀请码：%@（用于邀请他人）\n设备：%@\n验证数据：%@\n每日校验：通过\n每次校验：通过",
        action, data[@"application"][@"name"] ?: @"—", account[@"username"] ?: @"—",
        expired ? @"已过期" : @"正常", expiry, account[@"invitation_code"] ?: @"—",
        data[@"device"] ?: @"—", data[@"payload"] ?: @"—"];
    [self showResult:!expired];
}

- (void)showError:(NSString *)message {
    self.resultLabel.text = [NSString stringWithFormat:@"验证失败\n\n%@", message ?: @"请求错误"];
    [self showResult:NO];
}

- (void)showResult:(BOOL)success {
    self.resultCard.hidden = NO;
    self.resultCard.backgroundColor = success ? [UIColor colorWithRed:0.94 green:0.99 blue:0.97 alpha:1] : [UIColor colorWithRed:1 green:0.96 blue:0.96 alpha:1];
    self.resultCard.layer.borderColor = (success ? [UIColor colorWithRed:0.70 green:0.89 blue:0.80 alpha:1] : [UIColor colorWithRed:0.94 green:0.72 blue:0.74 alpha:1]).CGColor;
}

- (NSString *)displayDate:(id)value {
    if (![value isKindOfClass:NSString.class] || [value length] == 0) return @"未获得授权时长";
    NSISO8601DateFormatter *ISO = [NSISO8601DateFormatter new];
    NSDate *date = [ISO dateFromString:value];
    if (date == nil) {
        NSDateFormatter *plain = [NSDateFormatter new]; plain.dateFormat = @"yyyy-MM-dd HH:mm:ss";
        plain.timeZone = [NSTimeZone timeZoneForSecondsFromGMT:0]; date = [plain dateFromString:value];
    }
    if (date == nil) return value;
    NSDateFormatter *output = [NSDateFormatter new];
    output.locale = [NSLocale localeWithLocaleIdentifier:@"zh_CN"];
    output.timeZone = NSTimeZone.localTimeZone;
    output.dateFormat = @"yyyy-MM-dd HH:mm:ss";
    return [output stringFromDate:date];
}

- (BOOL)isExpiredDate:(id)value {
    if (![value isKindOfClass:NSString.class] || [value length] == 0) return YES;
    NSISO8601DateFormatter *ISO = [NSISO8601DateFormatter new];
    NSDate *date = [ISO dateFromString:value];
    if (date == nil) {
        NSDateFormatter *plain = [NSDateFormatter new];
        plain.dateFormat = @"yyyy-MM-dd HH:mm:ss";
        plain.timeZone = [NSTimeZone timeZoneForSecondsFromGMT:0];
        date = [plain dateFromString:value];
    }
    return date == nil || [date timeIntervalSinceNow] <= 0;
}

@end
