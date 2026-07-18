<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>MyBsPHP2 · 正式工程基线</title>
    <style>
        :root {
            color-scheme: light;
            --blue-700: #234fc7;
            --blue-600: #3564e8;
            --blue-100: #eaf1ff;
            --ink-900: #10203d;
            --ink-600: #60708d;
            --line: #dce5f4;
            --surface: #ffffff;
            --background: #f3f7fd;
            font-family: Inter, "PingFang SC", "Microsoft YaHei", system-ui, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink-900);
            background:
                radial-gradient(circle at 10% 15%, rgba(91, 132, 235, .18), transparent 28rem),
                linear-gradient(135deg, #eef4ff 0%, var(--background) 52%, #edf3fb 100%);
        }

        main {
            width: min(1120px, calc(100% - 40px));
            min-height: 100vh;
            margin: 0 auto;
            padding: 56px 0;
            display: grid;
            align-items: center;
        }

        .shell {
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(340px, .88fr);
            background: var(--surface);
            border: 1px solid rgba(205, 218, 239, .9);
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(56, 78, 123, .16);
        }

        .intro {
            padding: 64px;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, .94), rgba(239, 245, 255, .92)),
                var(--surface);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 70px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(145deg, var(--blue-600), var(--blue-700));
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(53, 100, 232, .25);
        }

        .brand strong {
            display: block;
            font-size: 20px;
        }

        .brand span {
            display: block;
            margin-top: 3px;
            color: var(--ink-600);
            font-size: 13px;
        }

        .eyebrow {
            margin: 0 0 12px;
            color: var(--blue-600);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .14em;
        }

        h1 {
            max-width: 590px;
            margin: 0;
            font-size: clamp(36px, 5vw, 60px);
            line-height: 1.1;
            letter-spacing: -.045em;
        }

        .lead {
            max-width: 610px;
            margin: 24px 0 0;
            color: var(--ink-600);
            font-size: 17px;
            line-height: 1.9;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 36px;
        }

        .button {
            min-height: 46px;
            padding: 0 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--ink-900);
            background: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .button-primary {
            color: #fff;
            border-color: var(--blue-600);
            background: var(--blue-600);
        }

        .status-panel {
            padding: 52px 44px;
            color: #fff;
            background: linear-gradient(155deg, #163986 0%, #234fc7 52%, #3564e8 100%);
        }

        .status-label {
            margin: 0;
            color: #bdd0ff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .status-panel h2 {
            margin: 12px 0 32px;
            font-size: 32px;
        }

        .status-list {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .status-list li {
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 14px;
            background: rgba(255, 255, 255, .09);
        }

        .status-list span {
            color: #cbd9ff;
        }

        .status-list strong {
            text-align: right;
        }

        .note {
            margin: 28px 0 0;
            padding-top: 24px;
            color: #cbd9ff;
            border-top: 1px solid rgba(255, 255, 255, .18);
            font-size: 14px;
            line-height: 1.7;
        }

        @media (max-width: 820px) {
            main {
                width: min(100% - 24px, 620px);
                padding: 20px 0;
            }

            .shell {
                grid-template-columns: 1fr;
                border-radius: 22px;
            }

            .intro,
            .status-panel {
                padding: 36px 28px;
            }

            .brand {
                margin-bottom: 44px;
            }
        }

        @media (max-width: 420px) {
            main {
                width: calc(100% - 20px);
            }

            .intro,
            .status-panel {
                padding: 30px 22px;
            }

            h1 {
                font-size: 38px;
            }

            .actions,
            .button {
                width: 100%;
            }

            .status-list li {
                align-items: flex-start;
                flex-direction: column;
            }

            .status-list strong {
                text-align: left;
            }
        }
    </style>
</head>
<body>
<main>
    <section class="shell" aria-labelledby="page-title">
        <div class="intro">
            <div class="brand">
                <div class="brand-mark">BS</div>
                <div>
                    <strong>MyBsPHP2</strong>
                    <span>授权与发卡管理系统</span>
                </div>
            </div>

            <p class="eyebrow">FORMAL LARAVEL BASELINE</p>
            <h1 id="page-title">W1 工程基线已经完成。</h1>
            <p class="lead">
                当前页面用于区分 Laravel 正式工程与 8090 旧原型。
                下一阶段从 W2 安装器开始，将后台认证及管理界面逐步迁入这里。
            </p>

            <div class="actions">
                <a class="button button-primary" href="http://127.0.0.1:8090/admin">查看当前后台原型</a>
                <a class="button" href="/api/v2/ping">检查 API 状态</a>
            </div>
        </div>

        <aside class="status-panel" aria-label="工程状态">
            <p class="status-label">CURRENT STATUS</p>
            <h2>W1 基线完成</h2>
            <ul class="status-list">
                <li><span>运行框架</span><strong>Laravel 13.20</strong></li>
                <li><span>PHP 环境</span><strong>PHP 8.3</strong></li>
                <li><span>数据环境</span><strong>MySQL 已隔离</strong></li>
                <li><span>接口基线</span><strong>REST v2 Ready</strong></li>
                <li><span>日志与审计</span><strong>W1-5 Ready</strong></li>
                <li><span>工程检查</span><strong>W1-6 Ready</strong></li>
                <li><span>下一任务</span><strong>W2-0 安装器调研</strong></li>
            </ul>
            <p class="note">
                8091 是正式 Laravel 工程；8090 暂时保留现有后台原型，
                便于功能和 UI 对照。
            </p>
        </aside>
    </section>
</main>
</body>
</html>
